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
	var messageListXhr = null;

	Radio.message.reply('entities', getMessageEntities);
	Radio.message.reply('entity', getMessageEntity);
	Radio.message.reply('bodies', fetchMessageBodies);
	Radio.message.reply('flag', flagMessage);
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
			replace: false, // Replace cached folder list
			force: false,
			filter: ''
		};
		_.defaults(options, defaults);

		// Do not cache search queries
		if (options.filter !== '') {
			options.cache = false;
		}

		// Abort previous requests
		if (messageListXhr !== null) {
			messageListXhr.abort();
		}

		var defer = $.Deferred();

		if (options.cache) {
			// Load cached version if available
			var messageList = require('cache').getMessageList(account, folder);
			if (!options.force && messageList) {
				_.each(messageList, function(msg) {
					folder.addMessage(msg);
				});
				defer.resolve(folder.messages, true);
				return defer.promise();
			}
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
			{
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});

		// TODO: folder.messages.fetch()
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
					var collection = folder.messages;
					if (options.replace) {
						collection.reset();
					}
					_.each(messages, function(msg) {
						folder.addMessage(msg);
					});
					defer.resolve(collection, false);
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

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {array} messageIds
	 * @returns {undefined}
	 */
	function fetchMessageBodies(account, folder, messageIds) {
		var defer = $.Deferred();

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

		if (uncachedIds.length > 0) {
			var Ids = uncachedIds.join(',');
			var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages?ids={ids}', {
				accountId: account.get('accountId'),
				folderId: folder.get('id'),
				ids: Ids
			});
			$.ajax(url, {
				type: 'GET',
				success: function(data) {
					defer.resolve(data);
				},
				error: function() {
					defer.reject();
				}
			});
		}

		return defer.promise();
	}

	function flagMessage(account, folder, message, flag, value) {
		var defer = $.Deferred();

		var flags = [flag, value];
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/flags',
			{
				accountId: account.get('accountId'),
				folderId: folder.get('id'),
				messageId: message.id
			});
		$.ajax(url, {
			type: 'PUT',
			data: {
				flags: _.object([flags])
			},
			success: function() {
				defer.resolve();
			},
			error: function() {
				defer.reject();
			}
		});

		return defer.promise();
	}

	/**
	 * @param {Account} account
	 * @param {object} message
	 * @param {object} options
	 * @returns {undefined}
	 */
	function sendMessage(account, message, options) {
		var defer = $.Deferred();

		var defaultOptions = {
			draftUID: null,
			aliasId: null
		};
		_.defaults(options, defaultOptions);
		var url = OC.generateUrl('/apps/mail/accounts/{id}/send', {
			id: account.get('id')
		});
		var data = {
			type: 'POST',
			success: function(data) {
				if (!!options.repliedMessage) {
					// Reply -> flag message as replied
					Radio.ui.trigger('messagesview:messageflag:set', options.repliedMessage.get('id'), 'answered', true);
				}

				defer.resolve(data);
			},
			error: function(xhr) {
				defer.reject(xhr);
			},
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
		};
		$.ajax(url, data);

		return defer.promise();
	}

	/**
	 * @param {Account} account
	 * @param {object} message
	 * @param {object} options
	 * @returns {undefined}
	 */
	function saveDraft(account, message, options) {
		var defer = $.Deferred();

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
				var draftsFolder = account.get('specialFolders').drafts;
				var deleteUrl =
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
						accountId: account.get('accountId'),
						folderId: draftsFolder,
						messageId: options.draftUID
					});
				$.ajax(deleteUrl, {
					type: 'DELETE'
				});
			}
			defer.resolve({
				uid: null
			});
		} else {
			var url = OC.generateUrl('/apps/mail/accounts/{id}/draft', {
				id: account.get('accountId')
			});
			var data = {
				type: 'POST',
				success: function(data) {
					if (options.draftUID !== null) {
						// update UID in message list
						var collection = Radio.ui.request('messagesview:collection');
						var message = collection.findWhere({id: options.draftUID});
						if (message) {
							message.set({id: data.uid});
							collection.set([message], {remove: false});
						}
					}
					defer.resolve(data);
				},
				error: function() {
					defer.reject();
				},
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
			};
			$.ajax(url, data);
		}
		return defer.promise();
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @returns {Deferred}
	 */
	function deleteMessage(account, folder, message) {
		var defer = $.Deferred();

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: require('state').currentAccount.get('accountId'),
			folderId: require('state').currentFolder.get('id'),
			messageId: message.get('id')
		});
		$.ajax(url, {
			data: {},
			type: 'DELETE',
			success: function() {
				var cache = require('cache');
				var state = require('state');
				cache.removeMessage(state.currentAccount, state.currentFolder, message.get('id'));

				defer.resolve();
			},
			error: function() {
				defer.reject();
			}
		});

		return defer.promise();
	}
});