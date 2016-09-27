/* global expect */

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
	'views/composerview',
	'models/accountcollection'
], function(ComposerView, AccountCollection) {
	describe('ComposerView', function() {
		var accounts;

		beforeEach(function() {
			accounts = new AccountCollection([
				{
					accountId: 13,
					name: 'Jane Nextcloud',
					emailAddress: 'jane@nextcloud.com',
					folders: [
						{
							id: 'inbox'
						}
					]
				},
				{
					accountId: 14,
					name: 'John Nextcloud',
					emailAddress: 'john@nextcloud.com',
					folders: [
						{
							id: 'inbox'
						}
					]
				}
			]);
		});

		it('creates a view to composer a new message', function() {
			var view = new ComposerView({
				accounts: accounts
			});

			expect(view.type).toBe('new');
			expect(view.isReply()).toBe(false);
			expect(view.account).toBe(accounts.at(0));
			expect(view.repliedMessage).toBeNull();
		});

		it('creates a reply composer', function() {
			var account = accounts.at(1);
			var folder = account.folders.first();
			var view = new ComposerView({
				accounts: accounts,
				account: account,
				folder: folder,
				type: 'reply'
			});

			expect(view.type).toBe('reply');
			expect(view.isReply()).toBe(true);
			expect(view.account).toBe(account);
			expect(view.folder).toBe(folder);
		});

		it('doesn\'t have draft UID at creation', function() {
			var view = new ComposerView({
				accounts: accounts
			});

			expect(view.draftUID).toBeUndefined();
		});

		it('creates the correct list of selectable accounts withoug aliases', function() {
			var view = new ComposerView({
				accounts: accounts
			});

			var expected = [
				{
					id: 1,
					accountId: 13,
					aliasId: null,
					emailAddress: 'jane@nextcloud.com',
					name: 'Jane Nextcloud'
				},
				{
					id: 2,
					accountId: 14,
					aliasId: null,
					emailAddress: 'john@nextcloud.com',
					name: 'John Nextcloud'
				}
			];
			expect(view.buildAliases()).toEqual(expected);
		});

		it('renders correctly', function() {
			var view = new ComposerView({
				accounts: accounts
			});

			view.render();

			var $el = view.$el;

			// Two accounts should be selectable
			expect($el.find('select.mail-account').
				children().length).toBe(2);
		});
	});
});