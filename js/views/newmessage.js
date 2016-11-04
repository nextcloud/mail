/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var NewMessageTemplate = require('text!templates/newmessage.html');

	return Marionette.View.extend({
		template: Handlebars.compile(NewMessageTemplate),
		accounts: null,
		ui: {
			button: '#mail_new_message'
		},
		events: {
			'click @ui.button': 'onClick'
		},
		initialize: function(options) {
			this.accounts = options.accounts;
			this.listenTo(options.accounts, 'add', this.onAccountsChanged);
		},
		onRender: function() {
			// Set the approriate ui state
			this.onAccountsChanged();
		},
		onAccountsChanged: function() {
			if (this.accounts.size === 0) {
				this.getUI('button').hide();
			} else {
				this.getUI('button').show();
			}
		},
		onClick: function(e) {
			e.preventDefault();
			Radio.ui.trigger('composer:show', e);
		}
	});
});
