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

	var Marionette = require('marionette');
	var AccountView = require('views/account');
	var AccountCollection = require('models/accountcollection');

	return Marionette.CollectionView.extend({
		collection: null,
		childView: AccountView,
		initialize: function() {
			this.collection = new AccountCollection();
		},
		getFolderById: function(accountId, folderId) {
			var activeAccount = accountId || require('state').currentAccountId;
			folderId = folderId || require('state').currentFolderId;
			activeAccount = this.collection.get(activeAccount);
			var activeFolder = activeAccount.get('folders').get(folderId);
			if (!_.isUndefined(activeFolder)) {
				return activeFolder;
			}
			var delimiter = activeAccount.get('delimiter');

			var firstPart = atob(folderId).split(delimiter)[0];
			activeFolder = activeAccount.get('folders').get(btoa(firstPart));
			activeFolder.attributes.folders.forEach(function(folder) {
				if (folder.id === folderId) {
					activeFolder = folder;
				}
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
			if (require('state').currentAccountId !== -1) {
				var activeAccount = require('state').currentAccountId;
				activeAccount = this.collection.get(activeAccount);
				activeEmail = ' - ' + activeAccount.get('email');
			}
			var activeFolder = this.getFolderById();
			var unread = activeFolder.unseen || activeFolder.get('unseen');
			var name = activeFolder.name || activeFolder.get('name');
			if (unread > 0) {
				window.document.title = name + ' (' + unread + ')' +
					// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
					activeEmail + ' - Mail - ' + oc_defaults.title;
				// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
			} else {
				window.document.title = name + activeEmail +
					// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
					' - Mail - ' + oc_defaults.title;
				// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
			}
		}
	});
});
