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

	var Marionette = require('backbone.marionette');
	var Radio = require('radio');
	var NewMessageView = require('views/newmessage');

	return Marionette.View.extend({

		el: '#app-navigation',

		regions: {
			newMessage: '#mail-new-message-fixed',
			accounts: {
				el: '#usergrouplist',
				replaceElement: true
			},
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
		},

		hide: function() {
			// TODO: move if or rename function
			if (require('state').accounts.length === 0) {
				this.$el.hide();
			}
		},

		onShowNewMessage: function() {
			this.showChildView('newMessage', new NewMessageView({
				accounts: this.options.accounts
			}));
		}
	});
});
