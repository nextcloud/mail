/* global Promise */

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

	var $ = require('jquery');
	var _ = require('underscore');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.message.reply('entities', getMessageEntities);
	Radio.message.reply('next-page', getNextMessagePage);
	Radio.message.reply('sync', syncMessages);
	Radio.message.reply('entity', getMessageEntity);
	Radio.message.reply('bodies', fetchMessageBodies);
	Radio.message.reply('flag', flagMessage);
	Radio.message.reply('move', moveMessage);
	Radio.message.reply('send', sendMessage);
	Radio.message.reply('draft', saveDraft);
	Radio.message.reply('delete', deleteMessage);

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
			filter: ''
		};
		_.defaults(options, defaults);

		// Do not cache search queries
		if (options.filter !== '') {
			options.cache = false;
		}

		return new Promise(function(resolve, reject) {
			if (options.cache && folder.get('messagesLoaded')) {
				resolve(folder.messages, true);
				return;
			}

			var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages', {
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});

			return Promise.resolve($.ajax(url, {
				data: {
					filter: options.filter
				},
				success: function(messages) {
					var collection = folder.messages;
					folder.addMessages(messages);
					folder.set('messagesLoaded', true);
					resolve(collection, false);
				},
				error: function(error, status) {
					if (status !== 'abort') {
						reject(error);
					}
				}
			}));
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getNextMessagePage(account, folder, options) {
		options = options || {};
		var defaults = {
			filter: ''
		};
		_.defaults(options, defaults);

		return new Promise(function(resolve, reject) {
			var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/page', {
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});
			var cursor = null;
			if (!folder.messages.isEmpty()) {
				cursor = folder.messages.last().get('id');
			}

			return Promise.resolve($.ajax(url, {
				data: {
					filter: options.filter,
					cursor: cursor
				},
				success: function(messages) {
					var collection = folder.messages;
					folder.addMessages(messages);
					folder.set('messagesLoaded', true);
					resolve(collection, false);
				},
				error: function(error, status) {
					if (status !== 'abort') {
						reject(error);
					}
				}
			}));
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function syncMessages(account, folder) {
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/sync', {
			accountId: account.get('accountId'),
			folderId: folder.get('id')
		});

		return Promise.resolve($.ajax(url, {
			data: {
				syncToken: folder.get('syncToken')
			}
		}));
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

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: messageId
		});

		// Load cached version if available
		var message = require('cache').getMessage(account,
			folder,
			messageId);
		if (message) {
			return Promise.resolve(message);
		}

		return new Promise(function(resolve, reject) {

			$.ajax(url, {
				type: 'GET',
				success: function(message) {
					resolve(message);
				},
				error: function(jqXHR, textStatus) {
					if (textStatus !== 'abort') {
						reject();
					}
				}
			});
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {array} messageIds
	 * @returns {Promise}
	 */
	function fetchMessageBodies(account, folder, messageIds) {
		var cachedMessages = [];
		var uncachedIds = [];

		_.each(messageIds, function(messageId) {
			var message = require('cache').getMessage(account, folder, messageId);
			if (message) {
				cachedMessages.push(message);
			} else {
				uncachedIds.push(messageId);
			}
		});

		return new Promise(function(resolve, reject) {
			if (uncachedIds.length > 0) {
				var Ids = uncachedIds.join(',');
				var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages?ids={ids}', {
					accountId: account.get('accountId'),
					folderId: folder.get('id'),
					ids: Ids
				});
				return Promise.resolve($.ajax(url, {
					type: 'GET'
				}));
			}
			reject();
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @param {string} flag
	 * @param {boolean} value
	 * @returns {Promise}
	 */
	function flagMessage(account, folder, message, flag, value) {
		var flags = [flag, value];
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/flags', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: message.id
		});
		return Promise.resolve($.ajax(url, {
			type: 'PUT',
			data: {
				flags: _.object([flags])
			}
		}));
	}

	/**
	 * @param {Account} sourceAccount
	 * @param {Folder} sourceFolder
	 * @param {Message} message
	 * @param {Account} destAccount
	 * @param {Folder} destFolder
	 * @returns {Promise}
	 */
	function moveMessage(sourceAccount, sourceFolder, message, destAccount,
		destFolder) {

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/move', {
			accountId: sourceAccount.get('accountId'),
			folderId: sourceFolder.get('id'),
			messageId: message.get('id')
		});
		return Promise.resolve($.ajax(url, {
			type: 'POST',
			data: {
				destAccountId: destAccount.get('accountId'),
				destFolderId: destFolder.get('id')
			}
		}));
	}

	/**
	 * @param {Account} account
	 * @param {object} message
	 * @param {object} options
	 * @returns {Promise}
	 */
	function sendMessage(account, message, options) {
		var defaultOptions = {
			draftUID: null,
			aliasId: null
		};
		_.defaults(options, defaultOptions);
		var url = OC.generateUrl('/apps/mail/accounts/{id}/send', {
			id: account.get('id')
		});
		return Promise.resolve($.ajax(url, {
			type: 'POST',
			data: {
				to: message.to,
				cc: message.cc,
				bcc: message.bcc,
				subject: message.subject,
				body: message.body,
				attachments: message.attachments,
				folderId: options.repliedMessage ? options.repliedMessage.get('folderId') : undefined,
				messageId: options.repliedMessage ? options.repliedMessage.get('messageId') : undefined,
				draftUID: options.draftUID,
				aliasId: options.aliasId
			}
		}));
	}

	/**
	 * @param {Account} account
	 * @param {object} message
	 * @param {object} options
	 * @returns {Promise}
	 */
	function saveDraft(account, message, options) {
		var defaultOptions = {
			folder: null,
			messageId: null,
			draftUID: null
		};
		_.defaults(options, defaultOptions);

		// TODO: replace by Backbone model method
		function undefinedOrEmptyString(prop) {
			return prop === undefined || prop === '';
		}
		var emptyMessage = true;
		var propertiesToCheck = ['to', 'cc', 'bcc', 'subject', 'body'];
		_.each(propertiesToCheck, function(property) {
			if (!undefinedOrEmptyString(message[property])) {
				emptyMessage = false;
			}
		});
		// END TODO

		if (emptyMessage) {
			if (options.draftUID !== null) {
				// Message is empty + previous draft exists -> delete it
				var draftsFolder = account.getSpecialFolder('draft');
				var deleteUrl =
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
						accountId: account.get('accountId'),
						folderId: draftsFolder,
						messageId: options.draftUID
					});
				return Promise.resolve($.ajax(deleteUrl, {
					type: 'DELETE'
				}));
			}
			return Promise.resolve({
				uid: null
			});
		}

		var url = OC.generateUrl('/apps/mail/accounts/{id}/draft', {
			id: account.get('accountId')
		});
		return Promise.resolve($.ajax(url, {
			type: 'POST',
			data: {
				to: message.to,
				cc: message.cc,
				bcc: message.bcc,
				subject: message.subject,
				body: message.body,
				attachments: message.attachments,
				folderId: options.folder ? options.folder.get('id') : null,
				messageId: options.repliedMessage ? options.repliedMessage.get('id') : null,
				uid: options.draftUID
			}
		}));
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @returns {Promise}
	 */
	function deleteMessage(account, folder, message) {
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: message.get('id')
		});
		return Promise.resolve($.ajax(url, {
			type: 'DELETE'
		}));
	}
});
