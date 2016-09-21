/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function getFolderEntities(account) {
		var defer = $.Deferred();

		var url = OC.generateUrl('apps/mail/accounts/{id}/folders',
			{
				id: account.get('accountId')
			});

		var promise = $.get(url);

		promise.done(function(data) {
			for (var prop in data) {
				if (prop === 'folders') {
					account.folders.reset();
					account.addFolder(data.folders);
				} else {
					account.set(prop, data[prop]);
				}
			}
			defer.resolve(account.folders);
		});

		promise.fail(function() {
			defer.reject();
		});

		return defer.promise();
	}

	return {
		getFolderEntities: getFolderEntities
	};
});
