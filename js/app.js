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

	var Marionette = require('marionette');
	var AppView = require('views/app');
	var SettingsView = require('views/settings');
	var NavigationView = require('views/navigation');
	var SetupView = require('views/setup');

	// Load controllers/services
	require('controller/accountcontroller');
	require('controller/foldercontroller');
	require('service/accountservice');
	require('service/folderservice');
	require('notification');

	var Mail = new Marionette.Application();

	var State = require('state');

	/**
	 * Set up view
	 */
	Mail.view = new AppView();

	Mail.on('before:start', function() {
		// Render settings menu
		this.view.navigation = new NavigationView();
		this.view.navigation.settings.show(new SettingsView({
			accounts: State.accounts
		}));
		this.view.setup.show(new SetupView({
			displayName: $('#user-displayname').text(),
			email: $('#user-email').text()
		}));
	});

	return Mail;
});
