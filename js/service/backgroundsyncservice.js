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

define(function(require) {
	'use strict';

	var Radio = require('radio');
	var _timer = null;
	var _accounts = null;

	Radio.sync.on('start', startBackgroundSync);

	var SYNC_INTERVAL = 30 * 1000; // twice a minute

	function startBackgroundSync(accounts) {
		_accounts = accounts;
		clearTimeout(_timer);
		triggerNextSync();
	}

	function triggerNextSync() {
		_timer = setTimeout(function() {
			var account;
			if (require('state').accounts.length === 1) {
				account = _accounts.first();
			} else {
				account = _accounts.get(-1);
			}
			sync(account);
		}, SYNC_INTERVAL);
	}

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function sync(account) {
		return Radio.sync.request('sync:folder', account.folders.first())
			.then(function(newMessages) {
				Radio.ui.trigger('notification:mail:show', newMessages);
			})
			.catch(function(e) {
				console.error(e);
			})
			.then(triggerNextSync);
	}

	return {
		sync: sync
	};
});
