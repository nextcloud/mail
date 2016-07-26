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

	var _ = require('underscore');
	var $ = require('jquery');
	var Marionette = require('marionette');
	var AccountView = require('views/account');
	var AccountCollection = require('models/accountcollection');
	var Radio = require('radio');

	/**
	 * @class NavigationAccountsView
	 */
	return Marionette.CollectionView.extend({
		collection: null,
		childView: AccountView,
		/**
		 * @returns {undefined}
		 */
		initialize: function() {
			this.collection = new AccountCollection();

			this.listenTo(Radio.ui, 'folder:changed', this.onFolderChanged);
			this.listenTo(Radio.folder, 'setactive', this.setFolderActive);
		},
		/**
		 * @returns {undefined}
		 */
		updateTitle: function() {
			var activeEmail = '';
			if (require('state').currentAccount.get('accountId') !== -1) {
				var activeAccount = require('state').currentAccount;
				activeEmail = ' - ' + activeAccount.get('email');
			}
			var activeFolder = require('state').currentFolder;
			var name = activeFolder.name || activeFolder.get('name');
			var count = 0;
			if (name === 'Drafts') {
				count = activeFolder.total || activeFolder.get('total');
			} else {
				count = activeFolder.unseen || activeFolder.get('unseen');
			}
			if (count > 0) {
				window.document.title = name + ' (' + count + ')' +
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
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {undefined}
		 */
		setFolderActive: function(account, folder) {
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

			folder.set('active', true);
		},
		/**
		 * @returns {undefined}
		 */
		onFolderChanged: function() {
			// Stop background message fetcher of previous folder
			require('background').messageFetcher.restart();
			// hide message detail view on mobile
			// TODO: find better place for this
			$('#mail-message').addClass('hidden-mobile');
		}
	});
});
