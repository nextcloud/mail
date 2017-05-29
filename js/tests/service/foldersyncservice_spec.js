/* global sinon, expect */

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
	'service/foldersyncservice',
	'models/account',
	'models/accountcollection',
	'models/folder',
	'models/message',
	'state'
], function(FolderSyncService, Account, AccountCollection, Folder, Message,
	State) {

	describe('FolderSyncService', function() {
		var server;

		beforeEach(function() {
			State.accounts = new AccountCollection();
			server = sinon.fakeServer.create();
		});

		afterEach(function() {
			State.accounts = new AccountCollection();
			server.restore();
		});

		it('syncs the sync token of a single folder', function(done) {
			var account = new Account({
				accountId: 15
			});
			var folder = new Folder({
				id: 'SU5CT1g=',
				syncToken: 'oldToken',
				account: account
			});
			folder.addMessage(new Message({
				id: 123
			}));
			folder.addMessage(new Message({
				id: 124
			}));

			var syncing = FolderSyncService.syncFolder(folder);

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [
						{
							id: 125
						}
					],
					changedMessages: [],
					vanishedMessages: []
				})
				);

			syncing.then(function() {
				expect(folder.messages.pluck('id')).toEqual([123, 124, 125]);
				done();
			}).catch(function(e) {
				console.error(e);
				done.fail(e);
			});
		});

		it('syncs new messages in a single folder', function(done) {
			var account = new Account({
				accountId: 15
			});
			var folder = new Folder({
				id: 'SU5CT1g=',
				syncToken: 'oldToken',
				account: account
			});
			folder.addMessage(new Message({
				id: 123
			}));
			folder.addMessage(new Message({
				id: 124
			}));

			var syncing = FolderSyncService.syncFolder(folder);

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [
						{
							id: 125
						}
					],
					changedMessages: [],
					vanishedMessages: []
				})
				);

			syncing.then(function() {
				expect(folder.messages.pluck('id')).toEqual([123, 124, 125]);
				done();
			}).catch(function(e) {
				console.error(e);
				done.fail(e);
			});
		});

		it('syncs changed messages in a single folder', function(done) {
			var account = new Account({
				accountId: 15
			});
			var folder = new Folder({
				id: 'SU5CT1g=',
				syncToken: 'oldToken',
				account: account
			});
			folder.addMessage(new Message({
				id: 123,
				subject: 'old subject'
			}));
			folder.addMessage(new Message({
				id: 124
			}));

			var syncing = FolderSyncService.syncFolder(folder);

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [],
					changedMessages: [
						{
							id: 123,
							subject: 'new subject'
						}
					],
					vanishedMessages: []
				})
				);

			syncing.then(function() {
				expect(folder.messages.get(123).
					get('subject')).toEqual('new subject');
				done();
			}).catch(function(e) {
				console.error(e);
				done.fail(e);
			});
		});

		it('syncs vanished messages in a single folder', function(done) {
			var account = new Account({
				accountId: 15
			});
			var folder = new Folder({
				id: 'SU5CT1g=',
				syncToken: 'oldToken',
				account: account
			});
			folder.addMessage(new Message({
				id: 123,
				subject: 'old subject'
			}));
			folder.addMessage(new Message({
				id: 124
			}));

			var syncing = FolderSyncService.syncFolder(folder);

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [],
					changedMessages: [],
					vanishedMessages: [
						123
					]
				})
				);

			syncing.then(function() {
				expect(folder.messages.pluck('id')).toEqual([124]);
				done();
			}).catch(function(e) {
				console.error(e);
				done.fail(e);
			});
		});

		it('syncs the unified inbox, even if no accounts are configured', function(
			done) {
			var account = new Account({
				accountId: -1,
				isUnified: true
			});
			var folder = new Folder({
				account: account
			});

			var syncing = FolderSyncService.syncFolder(folder);
			expect(server.requests.length).toBe(0);

			syncing.then(done).catch(done.fail);
		});

		it('syncs the unified inbox', function(done) {
			var account = new Account({
				accountId: -1,
				isUnified: true
			});
			var folder = new Folder({
				account: account
			});
			var acc1 = new Account({
				accountId: 1
			});
			var folder11 = new Folder({
				id: 'inbox11',
				specialRole: 'inbox'
			});
			var folder12 = new Folder({
				specialRole: 'sent'
			});
			var acc2 = new Account({
				accountId: 2
			});
			var folder21 = new Folder({
				id: 'inbox21',
				specialRole: 'inbox'
			});
			var folder22 = new Folder({
				id: 'inbox22',
				specialRole: 'inbox'
			});
			account.addFolder(folder);
			acc1.addFolder(folder11);
			acc1.addFolder(folder12);
			acc2.addFolder(folder21);
			acc2.addFolder(folder22);
			State.accounts.add(account);
			State.accounts.add(acc1);
			State.accounts.add(acc2);
			// Add some messages
			var message211 = new Message({
				id: 234,
				subject: 'old sub',
				account: acc2
			});
			folder21.addMessage(message211);
			folder.addMessage(folder21.messages.get(234));
			var message221 = new Message({
				id: 345,
				account: acc2
			});
			folder22.addMessage(message221);
			folder.addMessage(folder22.messages.get(345));

			var syncing = FolderSyncService.syncFolder(folder);
			expect(server.requests.length).toBe(3);

			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [
						{
							id: 123
						}
					],
					changedMessages: [],
					vanishedMessages: []
				})
				);
			server.requests[1].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [],
					changedMessages: [
						{
							id: 234,
							subject: 'new sub'
						}
					],
					vanishedMessages: []
				})
				);
			server.requests[2].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [],
					changedMessages: [],
					vanishedMessages: [
						345
					]
				})
				);

			syncing.then(function() {
				// New message saved to first inbox
				expect(folder11.messages.pluck('id')).toEqual([123]);
				// Update applied in second inbox
				expect(folder21.messages.get(234).get('subject')).toEqual('new sub');
				// Vanished message in third inbox is removed
				expect(folder22.messages.pluck('id')).toEqual([]);

				done();
			}).catch(done.fail);
		});

		it('syncs the unified inbox when an individual one changes', function(done) {
			var account = new Account({
				accountId: -1,
				isUnified: true
			});
			var folder = new Folder({
				account: account
			});
			var acc1 = new Account({
				accountId: 1
			});
			var folder11 = new Folder({
				specialRole: 'inbox'
			});
			var folder12 = new Folder({
				specialRole: 'sent'
			});
			var acc2 = new Account({
				accountId: 2
			});
			var folder21 = new Folder({
				specialRole: 'inbox'
			});
			var folder22 = new Folder({
				specialRole: 'inbox'
			});
			account.addFolder(folder);
			acc1.addFolder(folder11);
			acc1.addFolder(folder12);
			acc2.addFolder(folder21);
			acc2.addFolder(folder22);
			State.accounts.add(account);
			State.accounts.add(acc1);
			State.accounts.add(acc2);

			var syncing = FolderSyncService.syncFolder(folder11);
			expect(server.requests.length).toBe(1);

			server.requests[0].respond(
				200,
				{
					'Content-Type': 'application/json'
				},
				JSON.stringify({
					token: 'newToken',
					newMessages: [
						{
							id: 123
						}
					],
					changedMessages: [],
					vanishedMessages: []
				})
				);

			syncing.then(function() {
				// New message saved to first inbox
				expect(folder11.messages.pluck('id')).toEqual([123]);
				// Unified inbox was updated too
				expect(folder.messages.pluck('id')).toEqual([123]);

				done();
			}).catch(done.fail);
		});
	});

});