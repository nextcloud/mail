/* global SearchProxy */

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	// Enable ES6 promise polyfill
	require('es6-promise').polyfill();

	var $ = require('jquery');
	var Backbone = require('backbone');
	var Marionette = require('backbone.marionette');
	var OC = require('OC');
	var AppView = require('views/appview');
	var Search = require('search');
	var Radio = require('radio');
	var Router = require('router');
	var AccountController = require('controller/accountcontroller');
	var RouteController = require('routecontroller');

	// Load controllers/services
	require('controller/foldercontroller');
	require('controller/messagecontroller');
	require('service/accountservice');
	require('service/avatarservice');
	require('service/aliasesservice');
	require('service/attachmentservice');
	require('service/backgroundsyncservice');
	require('service/davservice');
	require('service/folderservice');
	require('service/foldersyncservice');
	require('service/messageservice');
	require('service/preferenceservice');
	require('util/notificationhandler');

	var Mail = Marionette.Application.extend({

		_useExternalAvatars: false,

		getUseExternalAvatars: function() {
			return this._useExternalAvatars;
		},

		/**
		 * Register the mailto protocol handler
		 */
		registerProtocolHandler: function() {
			if (window.navigator.registerProtocolHandler) {
				var url = window.location.protocol + '//' +
						window.location.host +
						OC.generateUrl('apps/mail/compose?uri=%s');
				try {
					window.navigator
							.registerProtocolHandler('mailto', url, OC.theme.name + ' Mail');
				} catch (e) {
				}
			}
		},

		requestNotificationPermissions: function() {
			Radio.ui.trigger('notification:request');
		},

		/**
		 * Register the actual search module in the search proxy
		 */
		setUpSearch: function() {
			new OCA.Search(Search.filter, function(arg) {
				Search.filter('');
			});
		},

		/**
		 * Start syncing accounts in the background
		 *
		 * @param {AccountCollection} accounts
		 */
		startBackgroundSync: function(accounts) {
			Radio.sync.trigger('start', accounts);
		}
	});

	Mail = new Mail();

	Mail.on('start', function() {
		this._useExternalAvatars = $('#external-avatars').val() === 'true';

		this.view = new AppView();

		Radio.ui.trigger('content:loading', t('mail', 'Loading accounts'));

		this.registerProtocolHandler();
		this.requestNotificationPermissions();
		this.setUpSearch();

		var _this = this;
		AccountController.loadAccounts().then(function(accounts) {
			_this.router = new Router({
				controller: new RouteController(accounts)
			});
			Backbone.history.start();
			_this.startBackgroundSync(accounts);
		});

		/**
		 * Detects pasted text by browser plugins, and other software.
		 * Check for changes in message bodies every second.
		 */
		setInterval((function() {
			// Begin the loop.
			return function() {

				// Define which elements hold the message body.
				var MessageBody = $('.message-body');

				/**
				 * If the message body is displayed and has content:
				 * Prepare the message body content for processing.
				 * If there is new message body content to process:
				 * Resize the text area.
				 * Toggle the send button, based on whether the message is ready or not.
				 * Prepare the new message body content for future processing.
				 */
				if (MessageBody.val()) {
					var OldMessageBody = MessageBody.val();
					var NewMessageBody = MessageBody.val();
					if (NewMessageBody !== OldMessageBody) {
						MessageBody.trigger('autosize.resize');
						OldMessageBody = NewMessageBody;
					}
				}
			};
		})(), 1000);
	});

	return Mail;
});
