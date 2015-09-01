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

	var Backbone = require('backbone');
	var Account = require('models/account');
	var OC = require('OC');

	return Backbone.Collection.extend({
		model: Account,
		url: OC.generateUrl('apps/mail/accounts'),
		comparator: function(folder) {
			return folder.get('id');
		}
	});
});
