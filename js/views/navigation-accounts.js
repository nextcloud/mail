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

	var _ = require('underscore');
	var $ = require('jquery');
	var Marionette = require('backbone.marionette');
	var AccountView = require('views/accountview');
	var Radio = require('radio');

	/**
	 * @class NavigationAccountsView
	 */
	return Marionette.CollectionView.extend({
		collection: null,
		childView: AccountView,
		tagName: 'li',
		/**
		 * @returns {undefined}
		 */
		initialize: function() {
			this.listenTo(Radio.ui, 'folder:changed', this.onFolderChanged);
			this.listenTo(Radio.folder, 'setactive', this.setFolderActive);
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {undefined}
		 */
		setFolderActive: function(account, folder) {
			// disable all other folders for all accounts
			require('state').accounts.each(function(acnt) {
				// TODO: useless? accounts.get(acnt.get('accountId')) === acnt ?
				var localAccount = require('state').accounts.get(acnt.get('accountId'));
				if (_.isUndefined(localAccount)) {
					return;
				}
				var folders = localAccount.folders;
				_.each(folders.models, function(folder) {
					folders.get(folder).set('active', false);
				});
			});

			if (folder) {
				folder.set('active', true);
			}
		},
		/**
		 * @returns {undefined}
		 */
		onFolderChanged: function() {
			// hide message detail view on mobile
			// TODO: find better place for this
			$('#mail-message').addClass('hidden-mobile');
		}
	});
});
