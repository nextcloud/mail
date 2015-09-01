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
	var storage = $.localStorage;

	var MessageCache = {
		getAccountPath: function(accountId) {
			return ['messages', accountId.toString()].join('.');
		},
		getFolderPath: function(accountId, folderId) {
			return [this.getAccountPath(accountId), folderId.toString()].join('.');
		},
		getMessagePath: function(accountId, folderId, messageId) {
			return [this.getFolderPath(accountId, folderId), messageId.toString()].join('.');
		}
	};

	var FolderCache = {
		getAccountPath: function(accountId) {
			return ['folders', accountId.toString()].join('.');
		},
		getFolderPath: function(accountId, folderId) {
			return [this.getAccountPath(accountId), folderId.toString()].join('.');
		}
	};

	function cleanUp(accounts) {
		var activeAccounts = accounts.map(function(account) {
			return account.get('accountId');
		});
		_.each(storage.get('messages'), function(account, accountId) {
			var isActive = _.any(activeAccounts, function(a) {
				return a === parseInt(accountId);
			});
			if (!isActive) {
				// Account does not exist anymore -> remove it
				storage.remove('messages.' + accountId);
			}
		});
	}

	function getFolderMessages(accountId, folderId) {
		var path = MessageCache.getFolderPath(accountId, folderId);
		return storage.isSet(path) ? storage.get(path) : null;
	}

	function getMessage(accountId, folderId, messageId) {
		var path = MessageCache.getMessagePath(accountId, folderId, messageId);
		if (storage.isSet(path)) {
			var message = storage.get(path);
			// Update the timestamp
			addMessage(accountId, folderId, message);
			return message;
		} else {
			return null;
		}
	}

	function addMessage(accountId, folderId, message) {
		var path = MessageCache.getMessagePath(accountId, folderId, message.id);
		// Add timestamp for later cleanup
		message.timestamp = Date.now();

		// Save the message to local storage
		storage.set(path, message);

		// Remove old messages (keep 20 most recently loaded)
		var messages = $.map(getFolderMessages(accountId, folderId), function(value) {
			return [value];
		});
		messages.sort(function(m1, m2) {
			return m2.timestamp - m1.timestamp;
		});
		var oldMessages = messages.slice(20, messages.length);
		_.each(oldMessages, function(message) {
			storage.remove(MessageCache.getMessagePath(accountId, folderId, message.id));
		});
	}

	function addMessages(accountId, folderId, messages) {
		_.each(messages, function(message) {
			addMessage(accountId, folderId, message);
		});
	}

	function removeMessage(accountId, folderId, messageId) {
		var message = getMessage(accountId, folderId, messageId);
		if (message) {
			// message exists in cache -> remove it
			storage.remove(MessageCache.getMessagePath(accountId, folderId, messageId));
		}
		var messageList = getMessageList(accountId, folderId);
		if (messageList) {
			// message list is cached -> remove message from it
			var newList = _.filter(messageList, function(message) {
				return message.id !== messageId;
			});
			addMessageList(accountId, folderId, newList);
		}
	}

	function getMessageList(accountId, folderId) {
		var path = FolderCache.getFolderPath(accountId, folderId);
		if (storage.isSet(path)) {
			return storage.get(path);
		} else {
			return null;
		}
	}

	function addMessageList(accountId, folderId, messages) {
		var path = FolderCache.getFolderPath(accountId, folderId);
		storage.set(path, messages);
	}

	function removeAccount(accountId) {
		// Remove cached message lists
		var path = FolderCache.getAccountPath(accountId);
		if (storage.isSet(path)) {
			storage.remove(path);
		}

		// Remove cached messages
		path = MessageCache.getAccountPath(accountId);
		if (storage.isSet(path)) {
			storage.remove(path);
		}

		// Unified inbox hack
		if (accountId !== -1) {
			// Make sure unified inbox cache is cleared to prevent
			// old message showing up on the next load
			removeAccount(-1);
		}
		// End unified inbox hack
	}

	return {
		cleanUp: cleanUp,
		getFolderMessages: getFolderMessages,
		getMessage: getMessage,
		addMessage: addMessage,
		addMessages: addMessages,
		removeMessage: removeMessage,
		getMessageList: getMessageList,
		addMessageList: addMessageList,
		removeAccount: removeAccount
	};
});
