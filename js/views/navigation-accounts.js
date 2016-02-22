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

	var $ = require('jquery');
	var Marionette = require('marionette');
	var AccountView = require('views/account');
	var AccountCollection = require('models/accountcollection');
	var Radio = require('radio');

	return Marionette.CollectionView.extend({
		collection: null,
		childView: AccountView,
		initialize: function() {
			this.collection = new AccountCollection();

			this.listenTo(Radio.ui, 'folder:changed', this.onFolderChanged);
			this.listenTo(Radio.folder, 'setactive', this.setFolderActive);
		},
		getFolderById: function(account, folderId) {
			var activeAccount = account || require('state').currentAccount;
			folderId = folderId || require('state').currentFolderId;
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
			// TODO: currentFolderId and currentAccount should be an attribute of this view
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
			if (require('state').currentAccount.get('accountId') !== -1) {
				var activeAccount = require('state').currentAccount;
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
		},
		setFolderActive: function(account, folderId) {
			Radio.ui.trigger('messagesview:filter:clear');

			// disable all other folders for all accounts
			require('state').accounts.each(function(acnt) {
				var localAccount = require('state').folderView.collection.get(acnt.get('accountId'));
				if (_.isUndefined(localAccount)) {
					return;
				}
				var folders = localAccount.get('folders');
				_.each(folders.models, function(folder) {
					folders.get(folder).set('active', false);
				});
			});

			require('state').folderView.getFolderById(account, folderId)
				.set('active', true);
		},
		onFolderChanged: function() {
			// Stop background message fetcher of previous folder
			require('background').messageFetcher.restart();
			// hide message detail view on mobile
			// TODO: find better place for this
			$('#mail-message').addClass('hidden-mobile');
		}
	});
});
