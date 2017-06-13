/* global expect */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

define([
	'views/accountview',
	'models/account',
], function(AccountView, Account) {

	describe('Account view', function() {

		var account;
		var accountView;

		beforeEach(function() {
			account = new Account();
			accountView = new AccountView({
				model: account
			});
		});

		it('has a delete button if the account is deletable', function() {
			accountView.render();

			expect(accountView.$el.html()).toContain('Delete');
		});

		it('has no delete button if the account is not deletable', function() {
			account.set('accountId', -2);
			accountView.render();

			expect(accountView.$el.html()).not.toContain('Delete');
		});
	});
});
