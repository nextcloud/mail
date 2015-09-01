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

	var $ = require('jquery');
	var SettingsAccountsView = require('views/settings-accounts');

	function loadAccounts() {
		var fetchingAccounts = require('app').request('account:entities');
		var UI = require('app').UI;

		$.when(fetchingAccounts).done(function(accounts) {
			UI.renderSettings();
			if (accounts.length === 0) {
				UI.addAccount();
			} else {
				var view = new SettingsAccountsView({
					el: '#settings-accounts',
					collection: accounts
				});
				view.render();
				var firstAccountId = accounts.at(0).get('accountId');
				accounts.each(function(a) {
					UI.loadFoldersForAccount(a.get('accountId'), firstAccountId);
				});
			}
		});
		$.when(fetchingAccounts).fail(function() {
			UI.showError(t('mail', 'Error while loading the accounts.'));
		});
	}

	return {
		loadAccounts: loadAccounts
	};
});
