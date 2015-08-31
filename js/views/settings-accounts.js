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

	var Marionette = require('marionette');
	var AccountView = require('views/settings-account');

	return Marionette.CollectionView.extend({
		tagName: 'ul',
		className: 'mailaccount-list',
		childView: AccountView,
		filter: function(account) {
			// Don't show unified inbox
			return account.get('accountId') !== -1;
		}
	});
});
