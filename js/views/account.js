/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var Backbone = require('backbone');
	var Handlebars = require('handlebars');
	var OC = require('OC');
	var Radio = require('radio');
	var FolderView = require('views/folder');
	var AccountTemplate = require('text!templates/account.html');

	var SHOW_COLLAPSED = Object.seal([
		'inbox',
		'flagged',
		'drafts',
	]);

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		model: null,
		template: Handlebars.compile(AccountTemplate),
		templateHelpers: function() {
			var toggleCollapseMessage = this.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders');
			return {
				isUnifiedInbox: this.model.get('accountId') === -1,
				toggleCollapseMessage: toggleCollapseMessage,
				hasMenu: this.model.get('accountId') !== -1
			};
		},
		collapsed: true,
		events: {
			'click .account-toggle-collapse': 'toggleCollapse',
			'click .app-navigation-entry-utils-menu-button button': 'toggleMenu',
			'click @ui.deleteButton': 'onDelete'
		},
		ui: {
			'menu': 'div.app-navigation-entry-menu',
			'deleteButton': 'button[class^="icon-delete"]'
		},
		// 'active' is needed to show the dotdotdot menu
		className: 'navigation-account',
		childView: FolderView,
		childViewContainer: '#mail_folders',
		menuShown: false,
		initialize: function(options) {
			this.model = options.model;
			this.collection = this.model.get('folders');
		},
		filter: function(child) {
			if (!this.collapsed) {
				return true;
			}
			var specialRole = child.get('specialRole');
			return SHOW_COLLAPSED.indexOf(specialRole) !== -1;
		},
		toggleCollapse: function() {
			this.collapsed = !this.collapsed;
			this.render();
			this.listenTo(Radio.ui, 'document:click', function(event) {
				var target = $(event.target);
				if (!this.$el.is(target.closest('.navigation-account'))) {
					// Click was not triggered by this element -> close menu
					this.menuShown = false;
					this.toggleMenuClass();
				}
			});
		},
		toggleMenu: function(e) {
			e.preventDefault();
			this.menuShown = !this.menuShown;
			this.toggleMenuClass();
		},
		toggleMenuClass: function() {
			this.ui.menu.toggleClass('open', this.menuShown);
		},
		onDelete: function(e) {
			e.stopPropagation();

			this.ui.deleteButton.removeClass('icon-delete').addClass('icon-loading-small');

			var account = this.model;

			$.ajax(OC.generateUrl('/apps/mail/accounts/{accountId}'), {
				data: {accountId: account.get('accountId')},
				type: 'DELETE',
				success: function() {
					// Delete cached message lists
					require('cache').removeAccount(account);

					// reload the complete page
					// TODO should only reload the app nav/content
					window.location.reload();
				},
				error: function() {
					OC.Notification.show(t('mail', 'Error while deleting account.'));
				}
			});
		}
	});
});
