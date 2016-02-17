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

	Radio.account.on('add', addAccount);
	Radio.account.on('load', loadAccounts);

	function addAccount() {
		// Todo: render SetupView into #app-content
		Radio.ui.trigger('composer:leave');
		Radio.ui.trigger('content:hide');
		Radio.ui.trigger('navigation:hide');
		Radio.ui.trigger('setup:show');
	}

	function loadAccounts() {
		var fetchingAccounts = Radio.account.request('entities');
		var UI = require('ui');

		$.when(fetchingAccounts).done(function(accounts) {
			if (accounts.length === 0) {
				addAccount();
			} else {
				var firstAccountId = accounts.at(0).get('accountId');
				accounts.each(function(a) {
					Radio.folder.trigger('init', a.get('accountId'), firstAccountId);
				});
			}
		});
		$.when(fetchingAccounts).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the accounts.'));
		});
	}

	return {
		loadAccounts: loadAccounts
	};
});
