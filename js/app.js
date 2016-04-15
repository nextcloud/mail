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
	var Marionette = require('marionette');
	var AppView = require('views/app');
	var Radio = require('radio');
	var Router = require('router');
	var AccountController = require('controller/accountcontroller');
	var RouteController = require('routecontroller');

	// Load controllers/services
	require('controller/foldercontroller');
	require('controller/messagecontroller');
	require('service/accountservice');
	require('service/folderservice');
	require('notification');

	var Mail = Marionette.Application.extend();

	Mail = new Mail();

	Mail.on('start', function() {
		this.view = new AppView();

		Radio.ui.trigger('content:loading');

		$.when(AccountController.loadAccounts()).done(function(accounts) {
			$('#app-navigation').removeClass('icon-loading');

			// Start fetching messages in background
			require('background').messageFetcher.start();

			this.router = new Router({
				controller: new RouteController(accounts)
			});
			Backbone.history.start();
		});
	});

	return Mail;
});
