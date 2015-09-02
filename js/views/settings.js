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
	var $ = require('jquery');
	var Handlebars = require('handlebars');
	var SettingsAccountsView = require('views/settings-accounts');

	return Marionette.LayoutView.extend({
		accounts: null,
		template: Handlebars.compile($('#mail-settings-template').html()),
		regions: {
			accountsList: '#settings-accounts'
		},
		initialize: function(options) {
			this.accounts = options.accounts;
		},
		onShow: function() {
			this.accountsList.show(new SettingsAccountsView({
				collection: this.accounts
			}));
		}
	});
});
