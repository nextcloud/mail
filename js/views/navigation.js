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
	var Marionette = require('marionette');
	var Radio = require('radio');
	var NewMessageView = require('views/newmessage');

	return Marionette.LayoutView.extend({
		el: '#app-navigation',
		regions: {
			newMessage: '#mail-new-message-fixed',
			accounts: '#app-navigation-accounts',
			settings: '#app-settings-content'
		},
		initialize: function() {
			this.bindUIElements();

			this.listenTo(Radio.ui, 'navigation:show', this.show);
			this.listenTo(Radio.ui, 'navigation:hide', this.hide);
			this.listenTo(Radio.ui, 'navigation:newmessage:show', this.onShowNewMessage);
		},
		render: function() {
			// This view doesn't need rendering
		},
		show: function() {
			this.$el.show();
			$('#app-navigation-toggle').css('background-image', '');
		},
		hide: function() {
			// TODO: move if or rename function
			if (require('state').accounts.length === 0) {
				this.$el.hide();
				$('#app-navigation-toggle').css('background-image', 'none');
			}
		},
		onShowNewMessage: function() {
			this.newMessage.show(new NewMessageView({
				accounts: this.options.accounts
			}));
		}
	});
});
