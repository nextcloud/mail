/* global Notification, SearchProxy */

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

	var $ = require('jquery');
	var Backbone = require('backbone');
	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var OC = require('OC');
	var AppView = require('views/app');
	var Radio = require('radio');
	var Router = require('router');
	var AccountController = require('controller/accountcontroller');
	var RouteController = require('routecontroller');

	// Load controllers/services
	require('controller/foldercontroller');
	require('controller/messagecontroller');
	require('service/accountservice');
	require('service/attachmentservice');
	require('service/folderservice');
	require('service/messageservice');
	require('notification');

	// Set marionette defaults
	Marionette.TemplateCache.prototype.compileTemplate = function(rawTemplate) {
		return Handlebars.compile(rawTemplate);
	};
	Marionette.ItemView.prototype.modelEvents = {change: 'render'};
	Marionette.CompositeView.prototype.modelEvents = {change: 'render'};

	var Mail = Marionette.Application.extend({
		registerProtocolHandler: function() {
			if (window.navigator.registerProtocolHandler) {
				var url = window.location.protocol + '//' +
					window.location.host +
					OC.generateUrl('apps/mail/compose?uri=%s');
				try {
					window.navigator
						.registerProtocolHandler('mailto', url, 'ownCloud Mail');
				} catch (e) {
				}
			}
		},
		requestNotificationPermissions: function() {
			// request permissions
			if (typeof Notification !== 'undefined') {
				Notification.requestPermission();
			}
		},
		setUpSearch: function() {
			SearchProxy.setFilter(require('search').filter);
		}
	});

	Mail = new Mail();

	Mail.on('start', function() {
		this.view = new AppView();

		Radio.ui.trigger('content:loading');

		this.registerProtocolHandler();
		this.requestNotificationPermissions();
		this.setUpSearch();

		$.when(AccountController.loadAccounts()).done(function(accounts) {
			$('#app-navigation').removeClass('icon-loading');

			// Start fetching messages in background
			require('background').messageFetcher.start();

			this.router = new Router({
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

	_.delay(function() {
		Mail.start();
	});
	return Mail;
});
