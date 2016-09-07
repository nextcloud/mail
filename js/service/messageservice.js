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
	Radio.message.reply('entity', getMessageEntity);
	Radio.message.reply('bodies', fetchMessageBodies);
	Radio.message.reply('flag', flagMessage);
	Radio.message.reply('send', sendMessage);
	Radio.message.reply('draft', saveDraft);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntities(account, folder, options) {
		options = options || {};
		var defaults = {
			filter: '',
			cache: false // Do *not* returned a cached version immediately
		};
		_.defaults(options, defaults);

		var defer = $.Deferred();
		if (options.cache && folder.get('messagesLoaded')) {
			return defer.resolve(folder.get('messages'), true);
		}
		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
			{
				accountId: account.get('accountId'),
				folderId: folder.get('id')
			});

		// TODO: folder.get('messages').fetch()
		$.ajax(url,
			{
				data: {
					from: options.from,
					to: options.to,
					filter: options.filter
				},
				success: function(messages) {
					var collection = folder.get('messages');
					var messageIds = [];
					_.each(messages, function(msg) {
						messageIds.push(msg.id);
						collection.add(msg, {
							merge: true
						});
					});
					if (options.from === 0) {
						// Reloading
						cleanUpCollection(collection, messageIds);
					}
					folder.set('messagesLoaded', true);
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

	function cleanUpCollection(collection, ids) {
		var toRemove = [];
		collection.forEach(function(message) {
			if (ids.indexOf(message.get('id')) === -1) {
				// Message was removed, so le't remove it
				// from the client collection too
				// TODO: use Backbone+horde sync and don't
				// discard the data
				toRemove.push(message.get('id'));
			}
		});
		_.each(toRemove, function(id) {
			collection.remove(id);
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} messageId
	 * @param {object} options
	 * @returns {Promise}
	 */
	function getMessageEntity(account, folder, message, options) {
		options = options || {};

		var defer = $.Deferred();

		if (message.get('hasDetails')) {
			return message;
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}', {
			accountId: account.get('accountId'),
			folderId: folder.get('id'),
			messageId: message.get('id')
		});
		$.ajax(url, {
			type: 'GET',
			success: function(messageDetails) {
				// do not override nested Backbone model 'flags'
				delete messageDetails.flags;

				message.set(messageDetails);
				message.set('hasDetails', true);
				defer.resolve(message);
			},
			error: function(jqXHR, textStatus) {
				if (textStatus !== 'abort') {
					defer.reject();
				}
			}
		});

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

		var uncachedIds = [];
		_.each(messageIds, function(messageId) {
			var message = folder.get('messages').get(messageId);
			if (!message) {
				// Weird, shouldn't have happened, but let's ignore this one
				return;
			}
			if (!message.get('hasDetails')) {
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
					// TODO: merge attributes
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
				if (!_.isNull(options.messageId)) {
					// Reply -> flag message as replied
					Radio.ui.trigger('messagesview:messageflag:set', options.messageId, 'answered', true);
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
				folderId: options.folder ? options.folder.get('id') : null,
				messageId: options.messageId,
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
					messageId: options.messageId,
					uid: options.draftUID
				}
			};
			$.ajax(url, data);
		}
		return defer.promise();
	}
});