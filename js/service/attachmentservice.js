/* global Promise */

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.message.reply('save:cloud', saveToFiles);
	Radio.message.reply('attachment:download', downloadAttachment);
	Radio.attachment.reply('upload:local', uploadLocalAttachment);
	Radio.attachment.reply('upload:abort', abortLocalAttachment);
	Radio.attachment.reply('upload:finished', uploadLocalAttachmentFinished);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @param {string} path
	 * @returns {Promise}
	 */
	function saveToFiles(account, folder, messageId, attachmentId, path) {
		var url = OC.generateUrl(
			'apps/mail/accounts/{accountId}/' +
			'folders/{folderId}/messages/{messageId}/' +
			'attachment/{attachmentId}', {
				accountId: account.get('accountId'),
				folderId: folder.get('id'),
				messageId: messageId,
				attachmentId: attachmentId
			});

		var options = {
			data: {
				targetPath: path
			},
			type: 'POST',
		};

		return Promise.resolve($.ajax(url, options));
	}

	/**
	 * @param {string} url
	 * @returns {Promise}
	 */
	function downloadAttachment(url) {
		return Promise.resolve($.ajax(url));
	}

	/**
	 * @param {File} file
	 * @param {function} localAttachmentModel
	 * @returns {Promise}
	 */
	function uploadLocalAttachment(file, localAttachmentModel) {
		var fd = new FormData();
		fd.append('attachment', file);

		var progressCallback = localAttachmentModel.onProgress;
		var url = OC.generateUrl('/apps/mail/attachments');

		return new Promise(function(resolve, reject) {
			$.ajax({
				url: url,
				type: 'POST',
				xhr: function() {
					var customXhr = $.ajaxSettings.xhr();
					// save the xhr into the model in order to :
					//  - distinguish upload and nextcloud file attachments
					//  - keep the upload status for later use
					localAttachmentModel.set('uploadRequest', customXhr);
					// and start the request
					if (customXhr.upload && _.isFunction(progressCallback)) {
						customXhr.upload.addEventListener(
							'progress',
							progressCallback.bind(localAttachmentModel),
							false);
					}
					return customXhr;
				},
				data: fd,
				processData: false,
				contentType: false
			})
			.done(function(data) {
				resolve(data.id);
			})
			.fail(function(err) {
				reject();
			});;
		});
	}

	/**
	 * This method is called when a local attachment upload should be aborted.
	 * If there is no upload ongoing, this method has no effect.
	 *
	 * @param {function} localAttachmentModel
	 */
	function abortLocalAttachment(localAttachmentModel) {
		var uploadRequest = localAttachmentModel.get('uploadRequest');
		if (uploadRequest && uploadRequest.readyState < 4) {
			uploadRequest.abort();
		}
		localAttachmentModel.collection.remove(localAttachmentModel);
	}

	/**
	 * This method is called when a local attachment upload has
	 * successfully finished. The server returned the db attachment id.
	 *
	 * @param {function} localAttachmentModel
	 * @param {number} filedId
	 */
	function uploadLocalAttachmentFinished(localAttachmentModel, fileId) {
		if (fileId === undefined || localAttachmentModel.get('progress') < 1) {
			localAttachmentModel.set('uploadStatus', 2);  // error
		}
		else {
			/* If we have a file id (file successfully uploaded), we saved it */
			localAttachmentModel.set('id', fileId);
			localAttachmentModel.set('uploadStatus', 3);  // success
		}
		// we are done with the request, just get rid of it!
		localAttachmentModel.unset('uploadRequest');
	}


	return {
		uploadLocalAttachment: uploadLocalAttachment,
		uploadLocalAttachmentFinished: uploadLocalAttachmentFinished
	};

});
