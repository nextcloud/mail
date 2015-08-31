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

	var AccountCollection = require('models/accountcollection');

	var state = {};

	var accounts = new AccountCollection();
	var currentFolderId = null;
	var currentAccountId = null;
	var currentMessageId = null;
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
		currentAccountId: {
			get: function() {
				return currentAccountId;
			},
			set: function(newId) {
				currentAccountId = newId;
			}
		},
		currentFolderId: {
			get: function() {
				return currentFolderId;
			},
			set: function(newId) {
				var oldId = currentFolderId;
				currentFolderId = newId;
				if (newId !== oldId) {
					require('app').UI.Events.onFolderChanged();
				}
			}
		},
		currentMessageId: {
			get: function() {
				return currentMessageId;
			},
			set: function(newId) {
				currentMessageId = newId;
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
