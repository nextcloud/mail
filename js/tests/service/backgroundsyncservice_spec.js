/* global expect, Promise, spyOn */

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
	'radio',
	'models/account',
	'models/folder',
	'service/backgroundsyncservice'
], function(Radio, Account, Folder, BackgroundSyncService) {
	describe('Background sync service', function() {
		it('fails', function(done) {
			spyOn(Radio.sync, 'request').and.returnValue(Promise.resolve([]));
			spyOn(Radio.ui, 'trigger');
			var account = new Account({
				accountId: -1,
				isUnified: true
			});
			var folder = new Folder({
				account: account
			});
			account.addFolder(folder);

			BackgroundSyncService.sync(account).then(function() {
				expect(Radio.sync.request).toHaveBeenCalledWith('sync:folder', folder);
				expect(Radio.ui.trigger).
					toHaveBeenCalledWith('notification:mail:show', []);
				done();
			}).catch(done.fail);
		});
	});
});
