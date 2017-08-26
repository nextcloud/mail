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

	var Marionette = require('backbone.marionette');
	var OC = require('OC');
	var Radio = require('radio');
	var SettingsTemplate = require('handlebars-loader!templates/settings.html');

	return Marionette.View.extend({
		accounts: null,
		template: SettingsTemplate,
		templateContext: function() {
			return {
				addAccountUrl: OC.generateUrl('apps/mail/#setup'),
				keyboardShortcutUrl: OC.generateUrl('apps/mail/#shortcuts')
			};
		},
		regions: {
			accountsList: '#settings-accounts'
		},
		events: {
			'click #new-mail-account': 'addAccount',
			'click #keyboard-shortcuts': 'showKeyboardShortcuts'
		},
		addAccount: function(e) {
			e.preventDefault();
			Radio.navigation.trigger('setup');
		},
		showKeyboardShortcuts: function(e) {
			e.preventDefault();
			Radio.navigation.trigger('keyboardshortcuts');
		}
	});
});
