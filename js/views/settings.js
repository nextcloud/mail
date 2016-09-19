/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var OC = require('OC');
	var Radio = require('radio');
	var SettingsTemplate = require('text!templates/settings.html');

	return Marionette.View.extend({
		accounts: null,
		template: Handlebars.compile(SettingsTemplate),
		templateHelpers: function() {
			return {
				addAccountUrl: OC.generateUrl('apps/mail/#setup')
			};
		},
		regions: {
			accountsList: '#settings-accounts'
		},
		events: {
			'click #new-mail-account': 'addAccount'
		},
		addAccount: function(e) {
			e.preventDefault();
			Radio.navigation.trigger('setup');
		}
	});
});
