/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define([
	'models/accountcollection',
	'models/account',
	'OC'
], function(AccountCollection, Account, OC) {
	describe('AccountCollection', function() {
		var collection;
		var account;

		beforeEach(function() {
			collection = new AccountCollection();
		});

		it('contains accounts', function() {
			expect(collection.model).toBe(Account);
		});

		it('uses the right URL', function() {
			spyOn(OC, 'generateUrl').and.returnValue('index.php/apps/mail/api/accounts');

			var url = collection.url();

			expect(OC.generateUrl).toHaveBeenCalled();
			expect(url).toBe('index.php/apps/mail/api/accounts');
		});

		it('sorts accounts by accountId', function() {
			account = new Account();
			account.set('accountId', 12);

			var cmp = collection.comparator(account);

			expect(cmp).toBe(12);
		});
	});
});
