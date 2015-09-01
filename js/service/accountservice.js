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

	function getAccountEntities() {
		var defer = $.Deferred();

		require('app').State.accounts.fetch({
			success: function(accounts) {
				require('app').Cache.cleanUp(accounts);
				defer.resolve(accounts);
			},
			error: function() {
				defer.reject();
			}
		});

		return defer.promise();
	}

	return {
		getAccountEntities: getAccountEntities
	};
});
