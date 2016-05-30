/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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
	var Backbone = require('backbone');
	var Radio = require('radio');
	var FolderController = require('controller/foldercontroller');

	/**
	 * @class RoutingController
	 */
	var RoutingController = function(accounts) {
		this.initialize(accounts);
	};

	RoutingController.prototype = {
		accounts: undefined,
		initialize: function(accounts) {
			this.accounts = accounts;

			Radio.navigation.on('folder', _.bind(this.showFolder, this));
			Radio.navigation.on('setup', _.bind(this.showSetup, this));
		},
		_navigate: function(route, options) {
			options = options || {};
			Backbone.history.navigate(route, options);
		},
		/**
		 * Handle mailto links
		 *
		 * @returns {boolean} whether the composer has been shown
		 */
		_handleMailto: function(params) {
			var composerOptions = {};
			params = params.split('&');

			_.each(params, function(param) {
				param = param.split('=');
				var key = param[0];
				var value = param[1];
				value = decodeURIComponent((value).replace(/\+/g, '%20'));

				switch (key) {
					case 'mailto':
					case 'to':
						composerOptions.to = value;
						break;
					case 'cc':
						composerOptions.cc = value;
						break;
					case 'bcc':
						composerOptions.bcc = value;
						break;
					case 'subject':
						composerOptions.subject = value;
						break;
					case 'body':
						composerOptions.body = value;
						break;
				}
			});

			this.default();
			Radio.ui.trigger('composer:show', composerOptions);
		},
		default: function() {
			this._navigate('');
			var _this = this;
			if (this.accounts.isEmpty()) {
				// No account configured -> show setup
				_this.showSetup();
				return;
			}

			// Show first folder of first account
			var firstAccount = this.accounts.at(0);
			var firstFolder = firstAccount.get('folders').at(0);
			_this.showFolder(firstAccount.get('accountId'), firstFolder.get('id'));
		},
		showFolder: function(accountId, folderId, noSelect) {
			this._navigate('accounts/' + accountId + '/folders/' + folderId);
			var _this = this;
			var account = this.accounts.get(accountId);
			if (_.isUndefined(account)) {
				// Unknown account id -> redirect
				Radio.ui.trigger('error:show', t('mail', 'Invalid account'));
				_this.default();
				return;
			}

			var folder = account.getFolderById(folderId);
			if (_.isUndefined(folder)) {
				folder = account.get('folders').at(0);
				Radio.ui.trigger('error:show', t('mail', 'Invalid folder'));
				this._navigate('accounts/' + accountId + '/folders/' + folder.get('id'));
			}
			FolderController.showFolder(account, folder, noSelect);
		},
		mailTo: function(params) {
			this._handleMailto(params);
		},
		showSetup: function() {
			this._navigate('setup');
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('navigation:hide');
			Radio.ui.trigger('setup:show');
		}
	};

	return RoutingController;
});
