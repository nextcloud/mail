/* global Promise, Infinity */

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
	Radio.message.reply('entity', getMessageEntity);
	Radio.message.reply('bodies', fetchMessageBodies);
	Radio.message.reply('flag', flagMessage);
	Radio.message.reply('move', moveMessage);
	Radio.message.reply('send', sendMessage);
	Radio.message.reply('draft', saveDraft);
	Radio.message.reply('delete', deleteMessage);

	function getFolderMessages(folder, options) {
		var defaults = {
			cache: false,
			filter: ''
		};
		_.defaults(options, defaults);

		// Do not cache search queries
		if (options.filter !== '') {
			options.cache = false;
		}
		if (options.cache && folder.get('messagesLoaded')) {
			return Promise.resolve(folder.messages, true);
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages', {
			accountId: folder.account.get('accountId'),
			folderId: folder.get('id')
		});

		return Promise.resolve($.ajax(url, {
			data: {
				filter: options.filter
			},
			error: function(error, status) {
				if (status !== 'abort') {
					console.error('error loading messages', error);
					throw new Error(error);
				}
			}
		})).then(function(messages) {
			var isSearching = options.filter !== '';
			var collection = folder.messages;

			if (isSearching) {
				// Get rid of other messages
				collection.reset();
				folder.set('messagesLoaded', false);
			} else {
				folder.set('messagesLoaded', true);
			}

			_.forEach(messages, function(msg) {
				msg.accountMail = folder.account.get('email');
			});
			folder.addMessages(messages);

			return collection;
		});
	}

	function getUnifiedFolderMessages(folder, options) {
		var allAccounts = require('state').accounts;
		// Fetch and merge other accounts
		return Promise.all(allAccounts.filter(function(acc) {
			// Select other accounts
			return acc.id !== folder.account.id;
		}).map(function(acc) {
			// Select its inboxes
			return acc.folders.filter(function(f) {
				return f.get('specialRole') === 'inbox';
			});
		}).reduce(function(acc, f) {
			// Flatten nested array
			return acc.concat(f);
		}, []).map(function(otherInbox) {
			return getFolderMessages(otherInbox, options)
				.then(function(messages) {
					folder.addMessages(messages.models);
				});
		})).then(function() {
			// Truncate after 20 messages
			// TODO: there might be a more efficient/convenient
			// Backbone.Collection or underscore helper function
			var top20 = folder.messages.slice(0, 20);
			folder.messages.reset();
			folder.addMessages(top20);
			return folder.messages;
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntities(account, folder, options) {
		options = options || {};

		if (account.get('isUnified')) {
			return getUnifiedFolderMessages(folder, options);
		} else {
			return getFolderMessages(folder, options);
		}
	}

	function getNextUnifiedMessagePage(unifiedFolder, options) {
		var allAccounts = require('state').accounts;
		var cursor = Infinity;
		if (!unifiedFolder.messages.isEmpty()) {
			cursor = unifiedFolder.messages.last().get('dateInt');
		}

		var individualAccounts = allAccounts.filter(function(account) {
			// Only non-unified accounts
			return !account.get('isUnified');
		});

		// Load data from folders where we do not have enough data
		return Promise.all(individualAccounts.map(function(account) {
			return Promise.all(account.folders.filter(function(folder) {
				// Only consider inboxes
				// TODO: generalize for other combined mailboxes
				return folder.get('specialRole') === 'inbox';
			}).filter(function(folder) {
				// Only fetch mailboxes that do not have enough data
				return folder.messages.filter(function(message) {
					return message.get('dateInt') < cursor;
				}).length < 21;
			}).map(function(folder) {
				return getNextMessagePage(folder.account, folder, options);
			}));
		})).then(function() {
			var allMessagesPage = individualAccounts.map(function(account) {
				return account.folders.filter(function(folder) {
					// Only consider inboxes
					// TODO: generalize for other combined mailboxes
					return folder.get('specialRole') === 'inbox';
				}).map(function(folder) {
					var messages = folder.messages.filter(function(message) {
						return message.get('dateInt') < cursor;
					});
					// Take all but the last message (acts as cursor)
					return messages.slice(0, messages.length - 2);
				}).reduce(function(all, messages) {
					return all.concat(messages);
				}, []);
			}).reduce(function(all, messages) {
				return all.concat(messages);
			}, []);

			var nextPage = allMessagesPage.sort(function(message) {
				return message.get('dateInt') * -1;
			}).slice(0, 20);

			nextPage.forEach(function(msg) {
				msg.set('unifiedId', unifiedFolder.messages.getUnifiedId(msg));
			});

			unifiedFolder.addMessages(nextPage, unifiedFolder);
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

		if (account.get('isUnified')) {
			return getNextUnifiedMessagePage(folder, options);
		} else {
			var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages', {
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});
			var cursor = null;
			if (!folder.messages.isEmpty()) {
				cursor = folder.messages.last().get('dateInt');
			}

			return new Promise(function(resolve, reject) {
				$.ajax(url, {
					method: 'GET',
					data: {
						filter: options.filter,
						cursor: cursor
					},
					success: resolve,
					error: function(error, status) {
						if (status !== 'abort') {
							reject(error);
						}
					}
				});
			}).then(function(messages) {
				var collection = folder.messages;
				folder.addMessages(messages);
				return collection;
			});
		}
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
				success: resolve,
				error: function(jqXHR, textStatus) {
					console.error('error loading message', jqXHR);
					if (textStatus !== 'abort') {
						reject(jqXHR);
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

	return {
		getNextMessagePage: getNextMessagePage
	};
});
