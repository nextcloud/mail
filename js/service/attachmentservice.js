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

	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.message.reply('save:cloud', saveToFiles);

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

});
