/* global oc_defaults */

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

	var Marionette = require('marionette'),
		AccountView = require('views/account'),
		AccountCollection = require('models/accountcollection');

	return Marionette.CollectionView.extend({
		// The collection will be kept here
		collection: null,
		childView: AccountView,
		initialize: function() {
			this.collection = new AccountCollection();
		},
		getFolderById: function(accountId, folderId) {
			var activeAccount = accountId || require('app').State.currentAccountId;
			folderId = folderId || require('app').State.currentFolderId;
			activeAccount = this.collection.get(activeAccount);
			var activeFolder = activeAccount.get('folders').get(folderId);
			if (!_.isUndefined(activeFolder)) {
				return activeFolder;
			}

			// bad hack to navigate down the tree ...
			var delimiter = activeAccount.get('delimiter');
			folderId = atob(folderId);
			activeFolder = activeAccount;
			var parts = folderId.split(delimiter);
			var k = '';
			_.each(parts, function(p) {
				if (k.length > 0) {
					k += delimiter;
				}
				k += p;

				var folders = activeFolder.folders || activeFolder.get('folders');
				activeFolder = folders.filter(function(f) {
					return f.id === btoa(k);
				}).shift();
			});
			return activeFolder;
		},
		changeUnseen: function(model, unseen) {
			// TODO: currentFolderId and currentAccountId should be an attribute of this view
			var activeFolder = this.getFolderById();
			if (unseen) {
				activeFolder.set('unseen', activeFolder.get('unseen') + 1);
			} else {
				if (activeFolder.get('unseen') > 0) {
					activeFolder.set('unseen', activeFolder.get('unseen') - 1);
				}
			}
			this.updateTitle();
		},
		updateTitle: function() {
			var activeEmail = '';
			if (require('app').State.currentAccountId !== -1) {
				var activeAccount = require('app').State.currentAccountId;
				activeAccount = this.collection.get(activeAccount);
				activeEmail = ' - ' + activeAccount.get('email');
			}
			var activeFolder = this.getFolderById();
			var unread = activeFolder.unseen;
			var name = activeFolder.name || activeFolder.get('name');

			if (unread > 0) {
				window.document.title = name + ' (' + unread + ')' +
					activeEmail + ' - Mail - ' + oc_defaults.title;
			} else {
				window.document.title = name + activeEmail +
					' - Mail - ' + oc_defaults.title;
			}
		}

	});
});
