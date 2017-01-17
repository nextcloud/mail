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
	var Marionette = require('marionette');
	var OC = require('OC');
	var AppView = require('views/appview');
	var Cache = require('cache');
	var Radio = require('radio');
	var Router = require('router');
	var AccountController = require('controller/accountcontroller');
	var RouteController = require('routecontroller');

	// Load controllers/services
	require('controller/foldercontroller');
	require('controller/messagecontroller');
	require('service/accountservice');
	require('service/attachmentservice');
	require('service/davservice');
	require('service/folderservice');
	require('service/messageservice');
	require('service/aliasesservice');
	require('util/notificationhandler');

	var Mail = Marionette.Application.extend({
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
		setUpSearch: function() {
			SearchProxy.setFilter(require('search').filter);
		}
	});

	Mail = new Mail();

	Mail.on('start', function() {
		this.view = new AppView();
		Cache.init();

		Radio.ui.trigger('content:loading');

		this.registerProtocolHandler();
		this.requestNotificationPermissions();
		this.setUpSearch();

		var _this = this;
		AccountController.loadAccounts().then(function(accounts) {
			_this.router = new Router({
				controller: new RouteController(accounts)
			});
			Backbone.history.start();
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
