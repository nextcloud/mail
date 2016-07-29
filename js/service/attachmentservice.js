/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @param {string} path
	 * @returns {Promise}
	 */
	function saveToFiles(account, folder, messageId, attachmentId, path) {
		var defer = $.Deferred();
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
			success: function() {
				defer.resolve();
			},
			error: function() {
				defer.reject();
			}
		};

		$.ajax(url, options);
		return defer.promise();
	}

	function downloadAttachment(url) {
		var defer = $.Deferred();

		$.ajax(url, {
			success: function(data) {
				defer.resolve(data);
			},
			error: function() {
				defer.reject();
			}
		});

		return defer.promise();
	}

	function uploadLocalAttachment(file, progressCallback) {
		var defer = $.Deferred();
		var fd = new FormData();
		fd.append('attachment', file);

		var url = OC.generateUrl('/apps/mail/attachments');
		$.ajax({
			url: url,
			type: 'POST',
			xhr: function() {
				var customXhr = $.ajaxSettings.xhr();
				if (customXhr.upload && _.isFunction(progressCallback)) {
					customXhr.upload.addEventListener('progress', progressCallback, false);
				}
				return customXhr;
			},
			data: fd,
			processData: false,
			contentType: false,
		}).done(function(data) {
			defer.resolve(data.id);
		}).fail(function() {
			defer.reject();
		});

		return defer.promise();
	}

});
