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

	var Marionette = require('backbone.marionette');
	var OC = require('OC');
	var Radio = require('radio');
	var FolderListView = require('views/folderlistview');
	var AccountTemplate = require('templates/account.html');

	return Marionette.View.extend({

		template: AccountTemplate,

		templateContext: function() {
			var toggleCollapseMessage = this.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders');
			return {
				isUnifiedInbox: this.model.get('accountId') === -1,
				toggleCollapseMessage: toggleCollapseMessage,
				hasMenu: this.model.get('accountId') !== -1,
				hasFolders: this.model.folders.length > 0,
				isDeletable: this.model.get('accountId') !== -2
			};
		},

		events: {
			'click .account-toggle-collapse': 'toggleCollapse',
			'click .app-navigation-entry-utils-menu-button button': 'toggleMenu',
			'click @ui.deleteButton': 'onDelete',
			'click @ui.settingsButton': 'showAccountSettings',
			'click @ui.email': 'onClick'
		},

		regions: {
			folders: {
				el: '.folders',
				replaceElement: true
			}
		},

		ui: {
			email: '.mail-account-email',
			menu: '.app-navigation-entry-menu',
			settingsButton: '.action-settings',
			deleteButton: '.action-delete'
		},

		/** @type {string} */
		className: 'navigation-account',

		/** @type {bool} */
		menuShown: false,

		/** @type {bool} */
		collapsed: true,

		/**
		 * @param {array} options
		 */
		initialize: function(options) {
			this.model = options.model;
		},

		toggleCollapse: function() {
			this.collapsed = !this.collapsed;
			this.render();
		},

		/**
		 * @param {Event} e
		 */
		toggleMenu: function(e) {
			e.preventDefault();
			this.menuShown = !this.menuShown;
			this.toggleMenuClass();
		},

		toggleMenuClass: function() {
			this.getUI('menu').toggleClass('open', this.menuShown);
		},

		/**
		 * @param {Event} e
		 */
		onDelete: function(e) {
			e.stopPropagation();

			this.getUI('deleteButton').find('.icon-delete').removeClass('icon-delete').addClass('icon-loading-small');

			var account = this.model;

			Radio.account.request('delete', account).then(function() {
				// reload the complete page
				// TODO should only reload the app nav/content
				window.location.reload();
			}, function() {
				OC.Notification.show(t('mail', 'Error while deleting account.'));
			});
		},

		/**
		 * @param {Event} e
		 */
		onClick: function(e) {
			e.preventDefault();
			if (this.model.folders.length > 0) {
				var accountId = this.model.get('accountId');
				var folderId = this.model.folders.first().get('id');
				Radio.navigation.trigger('folder', accountId, folderId);
			}
		},

		onRender: function() {
			this.listenTo(Radio.ui, 'document:click', function(event) {
				var target = $(event.target);
				if (!this.$el.is(target.closest('.navigation-account'))) {
					// Click was not triggered by this element -> close menu
					this.menuShown = false;
					this.toggleMenuClass();
				}
			});

			this.showChildView('folders', new FolderListView({
				collection: this.model.folders,
				collapsed: this.collapsed
			}));
		},

		/**
		 * @param {Event} e
		 */
		showAccountSettings: function(e) {
			this.toggleMenu(e);
			Radio.navigation.trigger('accountsettings', this.model.get('accountId'));

		}

	});
});
