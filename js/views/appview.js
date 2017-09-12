/* global oc_defaults */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var document = require('domready');
	var Marionette = require('backbone.marionette');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');
	var FolderContentView = require('views/foldercontent');
	var NavigationAccountsView = require('views/navigation-accounts');
	var SettingsView = require('views/settings');
	var ErrorView = require('views/errorview');
	var LoadingView = require('views/loadingview');
	var NavigationView = require('views/navigation');
	var SetupView = require('views/setupview');
	var AccountSettingsView = require('views/accountsettings');
	var KeyboardShortcutView = require('views/keyboardshortcuts');

	// Load handlebars helper
	require('views/helper');

	var ContentType = Object.freeze({
		ERROR: -2,
		LOADING: -1,
		FOLDER_CONTENT: 0,
		SETUP: 1,
		ACCOUNT_SETTINGS: 2,
		KEYBOARD_SHORTCUTS: 3
	});

	var AppView = Marionette.View.extend({
		el: '#app',
		accountsView: null,
		activeContent: null,
		regions: {
			content: '#app-content .mail-content',
			setup: '#setup'
		},
		initialize: function() {
			this.bindUIElements();

			// Global event handlers:
			this.listenTo(Radio.notification, 'favicon:change', this.changeFavicon);
			this.listenTo(Radio.ui, 'notification:show', this.showNotification);
			this.listenTo(Radio.ui, 'error:show', this.showError);
			this.listenTo(Radio.ui, 'setup:show', this.showSetup);
			this.listenTo(Radio.ui, 'foldercontent:show', this.showFolderContent);
			this.listenTo(Radio.ui, 'content:error', this.showContentError);
			this.listenTo(Radio.ui, 'content:loading', this.showContentLoading);
			this.listenTo(Radio.ui, 'title:update', this.updateTitle);
			this.listenTo(Radio.ui, 'accountsettings:show', this.showAccountSettings);
			this.listenTo(Radio.ui, 'search:set', this.setSearchQuery);
			this.listenTo(Radio.ui, 'sidebar:loading', this.showSidebarLoading);
			this.listenTo(Radio.ui, 'sidebar:accounts', this.showSidebarAccounts);
			this.listenTo(Radio.ui, 'keyboardShortcuts:show', this.showKeyboardShortcuts);

			// Hide notification favicon when switching back from
			// another browser tab
			$(document).on('show', this.onDocumentShow);

			$(document).on('click', this.onDocumentClick);

			// Listens to key strokes, and executes a function based
			// on the key combinations.
			$(document).keyup(this.onKeyUp);

			window.addEventListener('resize', this.onWindowResize);

			$(document).on('click', function(e) {
				Radio.ui.trigger('document:click', e);
			});

			// TODO: create marionette view and encapsulate events
			$(document).on('click', '#forward-button', function() {
				Radio.message.trigger('forward');
			});

			$(document).on('click', '.link-mailto', function(event) {
				Radio.ui.trigger('composer:show', event);
			});

			// TODO: create marionette view and encapsulate events
			// close message when close button is tapped on mobile
			$(document).on('click', '#mail-message-close', function() {
				$('#mail-message').addClass('hidden-mobile');
			});

			// TODO: create marionette view and encapsulate events
			// Show the images if wanted
			$(document).on('click', '#show-images-button', function() {
				$('#show-images-text').hide();
				$('iframe').contents().find('img[data-original-src]').each(function() {
					$(this).attr('src', $(this).attr('data-original-src'));
					$(this).show();
				});
				$('iframe').contents().find('[data-original-style]').each(function() {
					$(this).attr('style', $(this).attr('data-original-style'));
				});
			});

			// Render settings menu
			this.navigation = new NavigationView({
				accounts: require('state').accounts
			});
			this.navigation.showChildView('settings', new SettingsView());
		},
		onDocumentClick: function(event) {
			Radio.ui.trigger('document:click', event);
		},
		onDocumentShow: function(e) {
			e.preventDefault();
			Radio.notification.trigger('favicon:change', OC.filePath('mail', 'img', 'favicon.png'));
		},
		onKeyUp: function(e) {
			// Define which objects to check for the event properties.
			var key = e.keyCode || e.which;

			// Trigger the event only if no input or textarea is focused
			// and the CTRL key is not pressed
			if ($('input:focus').length === 0 &&
				$('textarea:focus').length === 0 &&
				!e.ctrlKey) {
				Radio.keyboard.trigger('keyup', e, key);
			}
		},
		onWindowResize: function() {
			// Resize iframe
			var iframe = $('#mail-content iframe');
			iframe.height(iframe.contents().find('html').height() + 20);
		},
		render: function() {
			// This view doesn't need rendering
		},
		changeFavicon: function(src) {
			$('link[rel="shortcut icon"]').attr('href', src);
		},
		showNotification: function(message) {
			OC.Notification.showTemporary(message);
		},
		showError: function(message) {
			OC.Notification.showTemporary(message);
			$('#mail_message').removeClass('icon-loading');
		},
		showSetup: function(account) {
			this.activeContent = ContentType.SETUP;
			this.showChildView('content', new SetupView({
				config: {
					accountName: $('#user-displayname').text(),
					emailAddress: $('#user-email').text()
				},
				account: account
			}));
		},
		showKeyboardShortcuts: function(account, folder) {
			this.activeContent = ContentType.KEYBOARD_SHORTCUTS;
			var options = {};
			options.account = account;
			options.folder = folder;
			this.showChildView('content', new KeyboardShortcutView(options));
		},
		showFolderContent: function(account, folder, options) {
			this.activeContent = ContentType.FOLDER_CONTENT;

			// Merge account, folder into a single options object
			options.account = account;
			options.folder = folder;

			this.showChildView('content', new FolderContentView(options));
		},
		showContentError: function(text, icon) {
			this.activeContent = ContentType.ERROR;
			this.showChildView('content', new ErrorView({
				text: text,
				icon: icon
			}));
		},
		showContentLoading: function(text) {
			this.activeContent = ContentType.LOADING;
			this.showChildView('content', new LoadingView({
				text: text
			}));
		},
		updateTitle: function() {
			var activeEmail = '';

			if (!require('state').currentAccount) {
				// Nothing to do
				return;
			}

			if (require('state').currentAccount.get('accountId') !== -1) {
				var activeAccount = require('state').currentAccount;
				activeEmail = ' - ' + activeAccount.get('email');
			}
			var activeFolder = require('state').currentFolder;
			var name = activeFolder.name || activeFolder.get('name');
			var count = 0;
			// TODO: use specialRole instead, otherwise this won't work with localized drafts folders
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
		showAccountSettings: function(account) {
			this.activeContent = ContentType.ACCOUNT_SETTINGS;

			this.showChildView('content', new AccountSettingsView({
				account: account
			}));
		},
		setSearchQuery: function(val) {
			val = val || '';
			$('#searchbox').val(val);
		},
		showSidebarLoading: function() {
			$('#app-navigation').addClass('icon-loading');
			if (this.navigation.getChildView('accounts')) {
				this.navigation.detachChildView('accounts');
			}
		},
		showSidebarAccounts: function() {
			$('#app-navigation').removeClass('icon-loading');
			// setup folder view
			this.navigation.showChildView('accounts', new NavigationAccountsView({
				collection: require('state').accounts
			}));
			// Also show the 'New message' button
			Radio.ui.trigger('navigation:newmessage:show');
		}
	});

	return AppView;
});
