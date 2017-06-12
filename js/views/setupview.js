/* global Promise */

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

	var _ = require('underscore');
	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var Radio = require('radio');
	var AccountController = require('controller/accountcontroller');
	var AccountFormView = require('views/accountformview');
	var ErrorView = require('views/errorview');
	var LoadingView = require('views/loadingview');
	var SetupTemplate = require('text!templates/setup.html');

	/**
	 * @class SetupView
	 */
	return Marionette.View.extend({

		/** @type {string} */
		className: 'container',

		/** @type {Function} */
		template: Handlebars.compile(SetupTemplate),

		/** @type {boolean} */
		_loading: false,

		/** @type {boolean} */
		_error: undefined,

		/** @type {Object} */
		_config: undefined,

		regions: {
			content: '.setup-content'
		},

		initialize: function(options) {
			this._config = options.config;
		},

		/**
		 * @returns {undefined}
		 */
		onRender: function() {
			if (!_.isUndefined(this._error)) {
				this.showChildView('content', new ErrorView({
					text: this._error,
					canRetry: true
				}));
			} else if (this._loading) {
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

		/**
		 * @private
		 * @param {Object} config
		 * @returns {Promise}
		 */
		onChildviewFormSubmit: function(config) {
			var _this = this;
			this._loading = true;
			this._config = config;
			this.render();

			return Radio.account.request('create', config).then(function() {
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
				console.error('could not create account:', error);
				// Show error view for a few seconds
				_this._loading = false;
				_this._error = error;
				_this.render();
			}).catch(console.error.bind(this));
		},

		onChildviewRetry: function() {
			this._loading = false;
			this._error = undefined;
			this.render();
		}

	});
});
