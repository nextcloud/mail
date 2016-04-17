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

	var $ = require('jquery');
	var Marionette = require('marionette');
	var AppView = require('views/app');
	var AccountController = require('controller/accountcontroller');
	var FolderController = require('./controller/foldercontroller');
	var Radio = require('radio');

	// Load controllers/services
	require('controller/foldercontroller');
	require('controller/messagecontroller');
	require('service/accountservice');
	require('service/folderservice');
	require('service/messageservice');
	require('notification');

	var Mail = Marionette.Application.extend({
		initialize: function() {
			this.listenTo(Radio.account, 'add', this.addAccount);
		},
		/**
		 * Handle mailto links
		 *
		 * @returns {boolean} whether the composer has been shown
		 */
		handleMailto: function() {
			var hash = window.location.hash;
			if (hash === '' || hash === '#') {
				// Nothing to do
				return false;
			}

			// Remove leading #
			hash = hash.substr(1);

			var composerOptions = {};
			var params = hash.split('&');

			_.each(params, function(param) {
				param = param.split('=');
				var key = param[0];
				var value = param[1];
				value = decodeURIComponent((value).replace(/\+/g, '%20'));

				switch (key) {
					case 'mailto':
					case 'to':
						composerOptions.to = value;
						break;
					case 'cc':
						composerOptions.cc = value;
						break;
					case 'bcc':
						composerOptions.bcc = value;
						break;
					case 'subject':
						composerOptions.subject = value;
						break;
					case 'body':
						composerOptions.body = value;
						break;
				}
			});

			window.location.hash = '';
			Radio.ui.trigger('composer:show', composerOptions);
			return true;
		},
		addAccount: function() {
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('navigation:hide');
			Radio.ui.trigger('setup:show');
		}
	});

	Mail = new Mail();

	Mail.on('start', function() {
		Radio.ui.trigger('content:loading');

		var loadingAccounts = AccountController.loadAccounts();
		var _this = this;
		$.when(loadingAccounts).done(function(accounts) {
			$('#app-navigation').removeClass('icon-loading');

			if (accounts.isEmpty()) {
				_this.addAccount();
			} else {
				if (!_this.handleMailto()) {
					Radio.ui.trigger('messagecontent:show');
				}

				var firstAccount = accounts.at(0);
				var firstFolder = firstAccount.get('folders').at(0);
				FolderController.showFolder(firstAccount, firstFolder);
			}

			// Start fetching messages in background
			require('background').messageFetcher.start();
		});
	});

	Mail.view = new AppView();

	return Mail;
});
