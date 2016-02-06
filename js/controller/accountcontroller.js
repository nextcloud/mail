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
	var Radio = require('radio');

	Radio.account.on('load', loadAccounts);

	function loadAccounts() {
		var fetchingAccounts = Radio.account.request('entities');
		var UI = require('ui');

		$.when(fetchingAccounts).done(function(accounts) {
			if (accounts.length === 0) {
				UI.addAccount();
			} else {
				var firstAccountId = accounts.at(0).get('accountId');
				accounts.each(function(a) {
					Radio.folder.trigger('init', a.get('accountId'), firstAccountId);
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
