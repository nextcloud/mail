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
	var Account = require('models/account');

	var MessageCache = {
		getAccountPath: function(account) {
			return ['messages', account.get('accountId').toString()].join('.');
		},
		getFolderPath: function(account, folderId) {
			return [this.getAccountPath(account), folderId.toString()].join('.');
		},
		getMessagePath: function(account, folderId, messageId) {
			return [this.getFolderPath(account, folderId), messageId.toString()].join('.');
		}
	};

	var FolderCache = {
		getAccountPath: function(account) {
			return ['folders', account.get('accountId').toString()].join('.');
		},
		getFolderPath: function(account, folderId) {
			return [this.getAccountPath(account), folderId.toString()].join('.');
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

	function getFolderMessages(account, folderId) {
		var path = MessageCache.getFolderPath(account, folderId);
		return storage.isSet(path) ? storage.get(path) : null;
	}

	function getMessage(account, folderId, messageId) {
		var path = MessageCache.getMessagePath(account, folderId, messageId);
		if (storage.isSet(path)) {
			var message = storage.get(path);
			// Update the timestamp
			addMessage(account, folderId, message);
			return message;
		} else {
			return null;
		}
	}

	function addMessage(account, folderId, message) {
		var path = MessageCache.getMessagePath(account, folderId, message.id);
		// Add timestamp for later cleanup
		message.timestamp = Date.now();

		// Save the message to local storage
		storage.set(path, message);

		// Remove old messages (keep 20 most recently loaded)
		var messages = $.map(getFolderMessages(account, folderId), function(value) {
			return [value];
		});
		messages.sort(function(m1, m2) {
			return m2.timestamp - m1.timestamp;
		});
		var oldMessages = messages.slice(20, messages.length);
		_.each(oldMessages, function(message) {
			storage.remove(MessageCache.getMessagePath(account, folderId, message.id));
		});
	}

	function addMessages(account, folderId, messages) {
		_.each(messages, function(message) {
			addMessage(account, folderId, message);
		});
	}

	function removeMessage(account, folderId, messageId) {
		var message = getMessage(account, folderId, messageId);
		if (message) {
			// message exists in cache -> remove it
			storage.remove(MessageCache.getMessagePath(account, folderId, messageId));
		}
		var messageList = getMessageList(account, folderId);
		if (messageList) {
			// message list is cached -> remove message from it
			var newList = _.filter(messageList, function(message) {
				return message.id !== messageId;
			});
			addMessageList(account, folderId, newList);
		}
	}

	function getMessageList(account, folderId) {
		var path = FolderCache.getFolderPath(account, folderId);
		if (storage.isSet(path)) {
			return storage.get(path);
		} else {
			return null;
		}
	}

	function addMessageList(account, folderId, messages) {
		var path = FolderCache.getFolderPath(account, folderId);
		storage.set(path, messages);
	}

	function removeAccount(account) {
		// Remove cached message lists
		var path = FolderCache.getAccountPath(account);
		if (storage.isSet(path)) {
			storage.remove(path);
		}

		// Remove cached messages
		path = MessageCache.getAccountPath(account);
		if (storage.isSet(path)) {
			storage.remove(path);
		}

		// Unified inbox hack
		if (account.get('accountId') !== -1) {
			// Make sure unified inbox cache is cleared to prevent
			// old message showing up on the next load
			removeAccount(new Account({accountId: -1}));
		}
		// End unified inbox hack
	}

	return {
		cleanUp: cleanUp,
		getMessage: getMessage,
		addMessage: addMessage,
		addMessages: addMessages,
		removeMessage: removeMessage,
		getMessageList: getMessageList,
		addMessageList: addMessageList,
		removeAccount: removeAccount
	};
});
