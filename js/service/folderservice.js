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

	var $ = require('jquery');
	var _ = require('underscore');
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

		return Promise.resolve($.get(url))
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
