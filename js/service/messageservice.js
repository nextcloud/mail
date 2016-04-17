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
	var messageListXhr = null;

	Radio.message.reply('entities', getMessageEntities);
	Radio.message.reply('entity', getMessageEntity);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntities(account, folder, options) {
		options = options || {};
		var defaults = {
			cache: false,
			replace: false, // Replace cached folder list
			force: false
		};
		_.defaults(options, defaults);

		// Abort previous requests
		if (messageListXhr !== null) {
			messageListXhr.abort();
		}

		var defer = $.Deferred();

		if (options.cache) {
			// Load cached version if available
			var messageList = require('cache').getMessageList(account, folder);
			if (!options.force && messageList) {
				defer.resolve(messageList, true);
				return defer.promise();
			}
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
			{
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});

		messageListXhr = $.ajax(url,
			{
				data: {
					from: options.from,
					to: options.to,
					filter: options.filter
				},
				success: function(messages) {
					if (options.replace || options.cache) {
						require('cache').addMessageList(account, folder, messages);
					}
					defer.resolve(messages, false);
				},
				error: function(error, status) {
					if (status !== 'abort') {
						defer.reject(error);
					}
				}
			});

		return defer.promise();
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntity(account, folder, messageId, options) {
		options = options || {};
		var defaults = {
			backgroundMode: false
		};
		_.defaults(options, defaults);

		var defer = $.Deferred();

		// Load cached version if available
		var message = require('cache').getMessage(account,
			folder,
			messageId);
		if (message) {
			defer.resolve(message);
			return defer.promise();
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: messageId
		});
		var xhr = $.ajax(url, {
			type: 'GET',
			success: function(message) {
				defer.resolve(message);
			},
			error: function(jqXHR, textStatus) {
				if (textStatus !== 'abort') {
					defer.reject();
				}
			}
		});
		if (!options.backgroundMode) {
			// Save xhr to allow aborting unneeded requests
			require('state').messageLoading = xhr;
		}

		return defer.promise();
	}
});