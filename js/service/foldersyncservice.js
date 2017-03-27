/* global Promise */

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

	var _ = require('underscore');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.message.reply('sync', syncFolder);

	/**
	 * @private
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function syncSingleFolder(folder) {
		var url = OC.generateUrl('/apps/mail/accounts/{accountId}/folders/{folderId}/sync', {
			accountId: folder.account.get('accountId'),
			folderId: folder.get('id')
		});

		return Promise.resolve($.ajax(url, {
			data: {
				syncToken: folder.get('syncToken'),
				uids: folder.messages.pluck('id')
			}
		})).then(function(syncResp) {
			folder.set('syncToken', syncResp.token);
			folder.addMessages(syncResp.newMessages);
			_.each(syncResp.changedMessages, function(msg) {
				var existing = folder.messages.get(msg.id);
				if (existing) {
					existing.set(msg);
				} else {
					// TODO: remove once we're confident this
					// condition never occurs
					throw new Error('non-existing message whily syncing');
				}
			});
			_.each(syncResp.vanishedMessages, function(id) {
				folder.messages.remove(id);
			});
		});
	}

	/**
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function syncFolder(folder) {
		var allAccounts = require('state').accounts;

		if (folder.account.get('isUnified')) {
			// Sync other accounts
			return Promise.all(allAccounts.filter(function(acc) {
				// Select other accounts
				return acc.id !== folder.account.id;
			}).map(function(acc) {
				// Select its inboxes
				return acc.folders.filter(function(f) {
					return f.get('specialRole') === 'inbox';
				});
			}).reduce(function(acc, f) {
				// Flatten nested array
				return acc.concat(f);
			}, []).map(syncSingleFolder));
		} else {
			return syncSingleFolder(folder);
		}
	}

	return {
		syncFolder: syncFolder
	};
});
