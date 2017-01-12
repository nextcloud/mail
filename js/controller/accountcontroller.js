/* global Promise */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016, 2017
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var FolderController = require('controller/foldercontroller');
	var Radio = require('radio');
	var UPDATE_INTERVAL = 5 * 60 * 1000; // 5 minutes

	Radio.account.on('load', loadAccounts);

	function startBackgroundChecks(accounts) {
		setInterval(function() {
			require('background').checkForNotifications(accounts);
		}, UPDATE_INTERVAL);
	}

	/**
	 * Load all accounts
	 *
	 * @returns {Deferred}
	 */
	function loadAccounts() {
		var defer = $.Deferred();
		// Do not show sidebar content until everything has been loaded
		Radio.ui.trigger('sidebar:loading');

		Radio.account.request('entities').then(function(accounts) {
			if (accounts.length === 0) {
				defer.resolve(accounts);
				Radio.navigation.trigger('setup');

				Radio.ui.trigger('sidebar:accounts');
			} else {
				Promise.all(accounts.map(function(account) {
					return FolderController.loadAccountFolders(account);
				}))
					.then(function() {
						defer.resolve(accounts);
					}, console.error.bind(this))
					.then(function() {
						// Show accounts regardless of the result of
						// loading the folders
						Radio.ui.trigger('sidebar:accounts');
					});
			}

			startBackgroundChecks(accounts);
		}).catch(function(e) {
			console.error(e);
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));

			// Show the accounts vie (again) on error to allow user to delete their failing accounts
			Radio.ui.trigger('sidebar:accounts');

			defer.reject();
		});

		return defer.promise();
	}

	return {
		loadAccounts: loadAccounts
	};
});
