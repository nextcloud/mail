/**
 * ownCloud - Mail
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
	var Radio = require('radio');
	var UPDATE_INTERVAL = 5 * 60 * 1000; // 5 minutes

	Radio.account.on('add', addAccount);
	Radio.account.on('load', loadAccounts);

	function addAccount() {
		Radio.ui.trigger('composer:leave');
		Radio.ui.trigger('navigation:hide');
		Radio.ui.trigger('setup:show');
	}

	function startBackgroundChecks(accounts) {
		setInterval((function(accounts) {
			require('background').checkForNotifications(accounts);
		}(accounts)), UPDATE_INTERVAL);
	}

	function loadAccounts() {
		var fetchingAccounts = Radio.account.request('entities');

		$.when(fetchingAccounts).done(function(accounts) {
			if (accounts.length === 0) {
				addAccount();
			} else {
				var firstAccount = accounts.at(0);
				accounts.each(function(account) {
					Radio.folder.trigger('init', account, firstAccount);
				});
			}

			startBackgroundChecks(accounts);
		});
		$.when(fetchingAccounts).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));
		});
	}

	return {
		loadAccounts: loadAccounts
	};
});
