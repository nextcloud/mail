/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var $ = require('jquery');
	var storage = $.sessionStorage;
	var Account = require('models/account');

	var MessageCache = {
		/**
		 * @param {Account} account
		 * @returns {string}
		 */
		getAccountPath: function(account) {
			return ['messages', account.get('accountId').toString()].join('.');
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {string}
		 */
		getFolderPath: function(account, folder) {
			return [this.getAccountPath(account), folder.get('id').toString()].join('.');
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @param {number} messageId
		 * @returns {string}
		 */
		getMessagePath: function(account, folder, messageId) {
			return [this.getFolderPath(account, folder), messageId.toString()].join('.');
		}
	};

	var FolderCache = {
		/**
		 * @param {Account} account
		 * @returns {string}
		 */
		getAccountPath: function(account) {
			return ['folders', account.get('accountId').toString()].join('.');
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {string}
		 */
		getFolderPath: function(account, folder) {
			return [this.getAccountPath(account), folder.get('id').toString()].join('.');
		}
	};

	function init() {
		console.log('initializing cache…');
		var installedVersion = $('#config-installed-version').val();
		if (storage.isSet('mail-app-version')) {
			var cachedVersion = storage.get('mail-app-version');
			if (cachedVersion !== installedVersion) {
				console.log('clearing cache because app version has changed');
				storage.removeAll();
			}
		} else {
			// Could be an old version -> clear data
			storage.removeAll();
		}
		storage.set('mail-app-version', installedVersion);
	}

	/**
	 * @param {AccountsCollection} accounts
	 * @returns {undefined}
	 */
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

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @returns {unresolved}
	 */
	function getFolderMessages(account, folder) {
		var path = MessageCache.getFolderPath(account, folder);
		return storage.isSet(path) ? storage.get(path) : null;
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} messageId
	 * @returns {unresolved}
	 */
	function getMessage(account, folder, messageId) {
		var path = MessageCache.getMessagePath(account, folder, messageId);
		if (storage.isSet(path)) {
			var message = storage.get(path);
			// Update the timestamp
			addMessage(account, folder, message);
			return message;
		} else {
			return null;
		}
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @returns {undefined}
	 */
	function addMessage(account, folder, message) {
		var path = MessageCache.getMessagePath(account, folder, message.id);
		// Add timestamp for later cleanup
		message.timestamp = Date.now();

		// Save the message to local storage
		storage.set(path, message);

		// Remove old messages (keep 20 most recently loaded)
		var messages = $.map(getFolderMessages(account, folder), function(value) {
			return [value];
		});
		messages.sort(function(m1, m2) {
			return m2.timestamp - m1.timestamp;
		});
		var oldMessages = messages.slice(20, messages.length);
		_.each(oldMessages, function(message) {
			storage.remove(MessageCache.getMessagePath(account, folder, message.id));
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} messages
	 * @returns {undefined}
	 */
	function addMessages(account, folder, messages) {
		_.each(messages, function(message) {
			addMessage(account, folder, message);
		});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @returns {undefined}
	 */
	function removeMessage(account, folder, messageId) {
		var message = getMessage(account, folder, messageId);
		if (message) {
			// message exists in cache -> remove it
			storage.remove(MessageCache.getMessagePath(account, folder, messageId));
		}
		var messageList = getMessageList(account, folder);
		if (messageList) {
			// message list is cached -> remove message from it
			var newList = _.filter(messageList, function(message) {
				return message.id !== messageId;
			});
			addMessageList(account, folder, newList);
		}
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @returns {unresolved}
	 */
	function getMessageList(account, folder) {
		var path = FolderCache.getFolderPath(account, folder);
		if (storage.isSet(path)) {
			return storage.get(path);
		} else {
			return null;
		}
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {type} messages
	 * @returns {undefined}
	 */
	function addMessageList(account, folder, messages) {
		var path = FolderCache.getFolderPath(account, folder);
		storage.set(path, messages);
	}

	/**
	 * @param {Account} account
	 * @returns {undefined}
	 */
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
		init: init,
		cleanUp: cleanUp,
		getMessage: getMessage,
		addMessage: addMessage,
		addMessages: addMessages,
		removeMessage: removeMessage,
		removeAccount: removeAccount
	};
});
