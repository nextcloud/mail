/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.folder.reply('entities', getFolderEntities);

	function getFolderEntities(accountId) {
		var defer = $.Deferred();

		var url = OC.generateUrl('apps/mail/accounts/{accountId}/folders',
			{
				accountId: accountId
			});

		var promise = $.get(url);

		promise.done(function(data) {
			defer.resolve(data);
		});

		promise.fail(function() {
			defer.reject();
		});
		// TODO: handle account fetching error
		return defer.promise();
	}

	return {
		getFolderEntities: getFolderEntities
	};
});
