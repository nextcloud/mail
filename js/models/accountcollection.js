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

	var Backbone = require('backbone');
	var Account = require('models/account');
	var OC = require('OC');

	/**
	 * @class AccountCollection
	 */
	var AccountCollection = Backbone.Collection.extend({
		model: Account,
		url: function() {
			return OC.generateUrl('apps/mail/accounts');
		},
		comparator: function(account) {
			return account.get('accountId');
		}
	});

	return AccountCollection;
});
