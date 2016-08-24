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
	 * @returns {Promise}
	 */
	function loadAccounts() {
		var defer = $.Deferred();
		var fetchingAccounts = Radio.account.request('entities');

		// Do not show sidebar content until everything has been loaded
		Radio.ui.trigger('sidebar:loading');

		$.when(fetchingAccounts).done(function(accounts) {
			if (accounts.length === 0) {
				defer.resolve(accounts);
				Radio.navigation.trigger('setup');

				Radio.ui.trigger('sidebar:accounts');
			} else {
				var loadingAccounts = accounts.map(function(account) {
					return FolderController.loadAccountFolders(account);
				});
				$.when.apply($, loadingAccounts).done(function() {
					defer.resolve(accounts);
				});
				$.when.apply($, loadingAccounts).always(function() {
					// Show accounts regardless of the result of
					// loading the folders
					Radio.ui.trigger('sidebar:accounts');
				});
			}

			startBackgroundChecks(accounts);
		});
		$.when(fetchingAccounts).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));

			// Show the accounts vie (again) on error to allow user to delete their failing accounts
			Radio.ui.trigger('sidebar:accounts');
		});
		
		return defer.promise();
	}

	return {
		loadAccounts: loadAccounts
	};
});
