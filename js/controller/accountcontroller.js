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

		$.when(fetchingAccounts).done(function(accounts) {
			if (accounts.length === 0) {
				defer.resolve(accounts);
				Radio.navigation.trigger('setup');
			} else {
				var loadingAccounts = accounts.map(function(account) {
					require('state').folderView.collection.add(account);
					return FolderController.loadFolder(account);
				});
				$.when.apply($, loadingAccounts).done(function() {
					defer.resolve(accounts);
				});
			}

			startBackgroundChecks(accounts);
		});
		$.when(fetchingAccounts).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));
		});
		
		return defer.promise();
	}

	return {
		loadAccounts: loadAccounts
	};
});
