/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	var messageListXhr = null;

	function get(url, options) {
		var defaultOptions = {
			ttl: 60000,
			cache: true,
			key: url
		};
		var allOptions = options || {};
		_.defaults(allOptions, defaultOptions);

		// don't cache for the time being
		allOptions.cache = false;
		if (allOptions.cache) {
			var cache = $.initNamespaceStorage(allOptions.key).localStorage;
			var ttl = cache.get('ttl');
			if (ttl && ttl < Date.now()) {
				cache.removeAll();
			}
			var item = cache.get('data');
			if (item) {
				options.success(item);
				return;
			}
		}
		return $.ajax(url, {
			data: {},
			type: 'GET',
			error: function(xhr, textStatus) {
				options.error(textStatus);
			},
			success: function(data) {
				if (allOptions.cache) {
					cache.set('data', data);
					if (typeof allOptions.ttl === 'number') {
						cache.set('ttl', Date.now() + allOptions.ttl);
					}
				}
				options.success(data);
			}
		});
	}

	function fetchMessage(account, folderId, messageId, options) {
		options = options || {};
		var defaults = {
			onSuccess: function() {
			},
			onError: function() {
			},
			backgroundMode: false
		};
		_.defaults(options, defaults);

		// Load cached version if available
		var message = require('cache').getMessage(account,
			folderId,
			messageId);
		if (message) {
			options.onSuccess(message);
			return;
		}

		var xhr = $.ajax(
			OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
				{
					accountId: account.get('accountId'),
					folderId: folderId,
					messageId: messageId
				}), {
			data: {},
			type: 'GET',
			success: options.onSuccess,
			error: options.onError
		});
		if (!options.backgroundMode) {
			// Save xhr to allow aborting unneded requests
			require('state').messageLoading = xhr;
		}
	}

	function fetchMessages(account, folderId, messageIds, options) {
		options = options || {};
		var defaults = {
			onSuccess: function() {
			},
			onError: function() {
			}
		};
		_.defaults(options, defaults);

		var cachedMessages = [];
		var uncachedIds = [];
		_.each(messageIds, function(messageId) {
			var message = require('cache').getMessage(account, folderId, messageId);
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
				folderId: folderId,
				ids: Ids
			});
			$.ajax(url, {
				type: 'GET',
				success: options.onSuccess,
				error: options.onError
			});
		}
	}

	function sendMessage(account, message, options) {
		var defaultOptions = {
			success: function() {
			},
			error: function() {
			},
			complete: function() {
			},
			accountId: null,
			draftUID: null
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

				options.success(data);
			},
			error: options.error,
			complete: options.complete,
			data: {
				to: message.to,
				cc: message.cc,
				bcc: message.bcc,
				subject: message.subject,
				body: message.body,
				attachments: message.attachments,
				folderId: options.folderId,
				messageId: options.messageId,
				draftUID: options.draftUID
			}
		};
		$.ajax(url, data);
	}

	function saveDraft(account, message, options) {
		var defaultOptions = {
			success: function() {
			},
			error: function() {
			},
			complete: function() {
			},
			accountId: null,
			folderId: null,
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
			options.success({
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
					options.success(data);
				},
				error: options.error,
				complete: options.complete,
				data: {
					to: message.to,
					cc: message.cc,
					bcc: message.bcc,
					subject: message.subject,
					body: message.body,
					attachments: message.attachments,
					folderId: options.folderId,
					messageId: options.messageId,
					uid: options.draftUID
				}
			};
			$.ajax(url, data);
		}
	}

	function fetchMessageList(account, folderId, options) {
		options = options || {};
		var defaults = {
			cache: false,
			replace: false, // Replace cached folder list
			force: false,
			onSuccess: function() {
			},
			onError: function() {
			},
			onComplete: function() {
			}
		};
		_.defaults(options, defaults);

		// Abort previous requests
		if (messageListXhr !== null) {
			messageListXhr.abort();
		}

		if (options.cache) {
			// Load cached version if available
			var messageList = require('cache').getMessageList(account, folderId);
			if (!options.force && messageList) {
				options.onSuccess(messageList, true);
				options.onComplete();
				return;
			}
		}

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
			{
				accountId: account.get('accountId'),
				folderId: folderId
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
						require('cache').addMessageList(account, folderId, messages);
					}
					options.onSuccess(messages, false);
				},
				error: function(error, status) {
					if (status !== 'abort') {
						options.onError(error);
					}
				},
				complete: options.onComplete
			});
	}

	return {
		get: get,
		fetchMessage: fetchMessage,
		fetchMessages: fetchMessages,
		fetchMessageList: fetchMessageList,
		sendMessage: sendMessage,
		saveDraft: saveDraft
	};
});
