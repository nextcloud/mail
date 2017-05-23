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

	var FolderController = require('controller/foldercontroller');
	var Radio = require('radio');

	/**
	 * Load all accounts
	 *
	 * @returns {Promise}
	 */
	function loadAccounts() {
		// Do not show sidebar content until everything has been loaded
		Radio.ui.trigger('sidebar:loading');

		return Radio.account.request('entities').then(function(accounts) {
			if (accounts.length === 0) {
				Radio.navigation.trigger('setup');
				Radio.ui.trigger('sidebar:accounts');
				return Promise.resolve(accounts);
			}

			return Promise.all(accounts.map(function(account) {
				return FolderController.loadAccountFolders(account);
			})).then(function() {
				return accounts;
			});
		}).then(function(accounts) {
			// Show accounts regardless of the result of
			// loading the folders
			Radio.ui.trigger('sidebar:accounts');

			return accounts;
		}, function(e) {
			console.error(e);
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));
		});
	}

	return {
		loadAccounts: loadAccounts
	};
});
