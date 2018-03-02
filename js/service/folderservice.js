/* global Promise */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016, 2017
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var fetch = require('nextcloud_fetch');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.folder.reply('entities', getFolderEntities);

	function buildUnifiedInbox(account) {
		account.addFolder({
			id: btoa('all-inboxes'),
			name: t('mail', 'All inboxes'),
			specialRole: 'inbox',
			isEmpty: false,
			accountId: -1,
			noSelect: false,
			delimiter: '.'
		});

		return Promise.resolve(account.folders);
	}

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function getFolderEntities(account) {
		var url = OC.generateUrl('apps/mail/api/accounts/{id}/folders', {
			id: account.get('accountId')
		});

		if (account.id === -1) {
			return buildUnifiedInbox(account);
		}

		return fetch(url)
				.then(function(resp) {
					if (resp.ok) {
						return resp.json();
					}
					throw Error('Could not load folders of account ' + account.get('accountId'), resp);
				})
				.then(function(data) {
					for (var prop in data) {
						if (prop === 'folders') {
							account.folders.reset();
							_.each(data.folders, account.addFolder, account);
						} else {
							account.set(prop, data[prop]);
						}
					}
					return account.folders;
				});
	}
});
