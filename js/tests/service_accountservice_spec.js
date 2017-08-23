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
	'service/accountservice',
	'OC',
], function(AccountService, OC) {

	describe('AccountService', function() {

		beforeEach(function() {
			jasmine.Ajax.install();
		});

		afterEach(function() {
			jasmine.Ajax.uninstall();
		});

		it('creates a new account on the server', function() {
			spyOn(OC, 'generateUrl').and.returnValue('index.php/apps/mail/accounts');

			var promise = AccountService.createAccount({
				email: 'email@example.com',
				password: '12345'
			});

			expect(OC.generateUrl).toHaveBeenCalledWith('apps/mail/accounts');

			expect(jasmine.Ajax.requests.count())
				.toBe(1);
			expect(jasmine.Ajax.requests.mostRecent().url)
				.toBe('index.php/apps/mail/accounts');
			jasmine.Ajax.requests.mostRecent().respondWith({
				'status': 200,
				'contentType': 'application/json',
				'responseText': '{}'
			});
		});

		it('handle account creation errors correctly', function(done) {
			spyOn(OC, 'generateUrl').and.returnValue('index.php/apps/mail/accounts');

			var creating = AccountService.createAccount({
				email: 'email@example.com',
				password: '12345'
			});

			expect(OC.generateUrl).toHaveBeenCalledWith('apps/mail/accounts');

			expect(jasmine.Ajax.requests.count()).toBe(1);
			expect(jasmine.Ajax.requests.mostRecent().url)
				.toBe('index.php/apps/mail/accounts');
			jasmine.Ajax.requests.mostRecent().respondWith({
				'status': 500,
				'contentType': 'application/json',
				'responseText': '{}'
			});

			creating.catch(done);
		});
	});
});
