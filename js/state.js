/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Radio = require('radio');
	var AccountCollection = require('models/accountcollection');

	var state = {};

	var accounts = new AccountCollection();
	var currentAccount = null;
	var currentFolder = null;
	var currentMessage = null;
	var currentMessageSubject = null;
	var currentMessageBody = '';
	var messagesLoading = null;
	var messageLoading = null;

	Object.defineProperties(state, {
		accounts: {
			get: function() {
				return accounts;
			},
			set: function(acc) {
				accounts = acc;
			}
		},
		currentAccount: {
			get: function() {
				return currentAccount;
			},
			set: function(account) {
				currentAccount = account;
			}
		},
		currentFolder: {
			get: function() {
				return currentFolder;
			},
			set: function(newFolder) {
				var oldFolder = currentFolder;
				currentFolder = newFolder;
				if (newFolder !== oldFolder) {
					Radio.ui.trigger('folder:changed');
				}
			}
		},
		currentMessage: {
			get: function() {
				return currentMessage;
			},
			set: function(newMessage) {
				currentMessage = newMessage;
			}
		},
		currentMessageSubject: {
			get: function() {
				return currentMessageSubject;
			},
			set: function(subject) {
				currentMessageSubject = subject;
			}
		},
		currentMessageBody: {
			get: function() {
				return currentMessageBody;
			},
			set: function(body) {
				currentMessageBody = body;
			}
		},
		messagesLoading: {
			get: function() {
				return messagesLoading;
			},
			set: function(loading) {
				messagesLoading = loading;
			}
		},
		messageLoading: {
			get: function() {
				return messageLoading;
			},
			set: function(loading) {
				messageLoading = loading;
			}
		}
	});

	return state;
});
