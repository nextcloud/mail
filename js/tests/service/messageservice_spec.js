/* global sinon */

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
	'underscore',
	'service/messageservice',
	'models/account',
	'models/accountcollection',
	'models/folder',
	'models/message',
	'state'
], function(_, MessageService, Account, AccountCollection, Folder, Message,
	State) {
	'use strict';

	describe('MessageService', function() {
		var server;

		beforeEach(function() {
			State.accounts = new AccountCollection();
			server = sinon.fakeServer.create();
		});

		afterEach(function() {
			State.accounts = new AccountCollection();
			server.restore();
		});

		function getTestAccounts(nrAccounts) {
			var unifiedAccount = new Account({
				accountId: -1,
				isUnified: true
			});
			var unifiedInbox = new Folder({
				id: 'inbox',
				specialRole: 'inbox',
				account: unifiedAccount
			});
			unifiedAccount.addFolder(unifiedInbox);
			State.accounts.add(unifiedAccount);

			return _.range(1, nrAccounts + 1).map(function(id) {
				var account = new Account({
					accountId: id
				});
				var inbox = new Folder({
					id: 'inbox',
					specialRole: 'inbox',
					account: account
				});
				var otherFolder = new Folder({
					id: 'something',
					account: account
				});
				account.addFolder(inbox);
				account.addFolder(otherFolder);
				State.accounts.add(account);
				return account;
			});
		}

		function createMessages(count) {
			return _.range(count).map(function(id) {
				return new Message({
					id: id * 100,
					dateInt: id * 1000
				});
			});
		}

		it('fetches the next page of an individual folder', function(done) {
			var account = new Account();
			var folder = new Folder({
				id: 'XYZ'
			});
			account.addFolder(folder);
			var fetching = MessageService.getNextMessagePage(account, folder);

			expect(server.requests.length).toBe(1);

			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify([
					{
						id: 123,
						subject: 'hello'
					}
				])
				);

			expect(server.requests.length).toBe(1);

			fetching.then(function(messages) {
				expect(messages.length).toBe(1);
				done();
			}).catch(done.fail);
		});

		it('propagates fetch errors', function(done) {
			var account = new Account();
			var folder = new Folder({
				id: 'XYZ',
				account: account
			});
			account.addFolder(folder);
			var fetching = MessageService.getNextMessagePage(account, folder);

			expect(server.requests.length).toBe(1);

			server.requests[0].respond(
				500,
				{
					'Content-Type': 'application/json'
				});

			fetching.then(done.catch).catch(done);
		});

		it('loads unified inbox page and uses local data if enough data is available', function(
			done) {
			var testAccounts = getTestAccounts(2);
			var unifiedAccount = State.accounts.get(-1);
			var unifiedInbox = unifiedAccount.folders.first();
			var inbox1 = testAccounts[0].folders.first();
			var inbox2 = testAccounts[1].folders.first();

			inbox1.addMessages(createMessages(25));
			inbox2.addMessages(createMessages(25));

			var fetching = MessageService.getNextMessagePage(unifiedAccount, unifiedInbox);

			expect(server.requests.length).toBe(0);

			fetching.then(function() {
				expect(unifiedInbox.messages.length).toBe(20);
				done();
			}).catch(done.fail);
		});

		it('loads unified inbox page and fetches pages where necessary', function(
			done) {
			var testAccounts = getTestAccounts(2);
			var unifiedAccount = State.accounts.get(-1);
			var unifiedInbox = unifiedAccount.folders.first();
			var inbox1 = testAccounts[0].folders.first();
			var inbox2 = testAccounts[1].folders.first();

			inbox1.addMessages(createMessages(25));
			inbox2.addMessages(createMessages(5));

			var fetching = MessageService.getNextMessagePage(unifiedAccount, unifiedInbox);

			expect(server.requests.length).toBe(1);

			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify([
					{
						id: 123,
						subject: 'hello',
						dateInt: 26000
					}
				])
				);

			fetching.then(function() {
				expect(unifiedInbox.messages.length).toBe(20);
				done();
			}).catch(done.fail);
		});

	});

});