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
	var Radio = require('radio');

	return Marionette.LayoutView.extend({
		el: $('#app-navigation'),
		regions: {
			accounts: '#app-navigation-accounts',
			settings: '#app-settings-content'
		},
		initialize: function() {
			this.bindUIElements();

			this.listenTo(Radio.ui, 'navigation:hide', this.hide);
		},
		render: function() {
			// This view doesn't need rendering
		},
		hide: function() {
			// TODO: move if or rename function
			if (require('state').accounts.length === 0) {
				this.$el.hide();
				// TODO: move
				$('#app-navigation-toggle').css('background-image', 'none');
			}
		}
	});
});
