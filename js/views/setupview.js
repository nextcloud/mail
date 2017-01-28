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

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var Radio = require('radio');
	var AccountController = require('controller/accountcontroller');
	var AccountFormView = require('views/accountformview');
	var LoadingView = require('views/loadingview');
	var SetupTemplate = require('text!templates/setup.html');

	/**
	 * @class SetupView
	 */
	return Marionette.View.extend({

		className: 'container',

		template: Handlebars.compile(SetupTemplate),

		/** @type {boolean} */
		_loading: false,

		_config: undefined,

		regions: {
			content: '.setup-content'
		},

		onRender: function() {
			if (this._loading) {
				// Rendering the first time
				this.showChildView('content', new LoadingView({
					text: t('mail', 'Setting up your account')
				}));
			} else {
				// Re-rending because an error occurred
				this.showChildView('content', new AccountFormView({
					config: this._config
				}));
			}
		},

		onChildviewFormSubmit: function(config) {
			this._loading = true;
			this._config = config;
			this.render();

			Radio.account.request('create', config).then(function() {
				Radio.ui.trigger('navigation:show');
				Radio.ui.trigger('content:loading');
				// reload accounts
				return AccountController.loadAccounts();
			}).then(function(accounts) {
				// Let's assume there's at least one account after a successful
				// setup, so let's show the first one (could be the unified inbox)
				var firstAccount = accounts.first();
				var firstFolder = firstAccount.folders.first();
				Radio.navigation.trigger('folder', firstAccount.get('accountId'), firstFolder.get('id'));
			}).catch(function(error) {
				Radio.ui.trigger('error:show', error);

				// Show form again
				this._loading = false;
				this.render();
			}.bind(this));
		}

	});
});
