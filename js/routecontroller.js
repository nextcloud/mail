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
			Radio.navigation.on('search', _.bind(this.searchFolder, this));
			Radio.navigation.on('setup', _.bind(this.showSetup, this));
			Radio.navigation.on('accountsettings', _.bind(this.showAccountSettings, this));
			Radio.navigation.on('keyboardshortcuts', _.bind(this.showKeyboardShortcuts, this));
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
			var firstFolder = firstAccount.folders.at(0);
			_this.showFolder(firstAccount.get('accountId'), firstFolder.get('id'));
		},
		showFolder: function(accountId, folderId) {
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
				folder = account.folders.at(0);
				Radio.ui.trigger('error:show', t('mail', 'Invalid folder'));
				this._navigate('accounts/' + accountId + '/folders/' + folder.get('id'));
			}
			FolderController.showFolder(account, folder);
		},
		searchFolder: function(accountId, folderId, query) {
			if (!query || query === '') {
				this.showFolder(accountId, folderId);
				return;
			}

			this._navigate('accounts/' + accountId + '/folders/' + folderId + '/search/' + query);
			var account = this.accounts.get(accountId);
			if (_.isUndefined(account)) {
				// Unknown account id -> redirect
				Radio.ui.trigger('error:show', t('mail', 'Invalid account'));
				this.default();
				return;
			}

			var folder = account.getFolderById(folderId);
			if (_.isUndefined(folder)) {
				folder = account.folders.at(0);
				Radio.ui.trigger('error:show', t('mail', 'Invalid folder'));
				this._navigate('accounts/' + accountId + '/folders/' + folder.get('id'));
			}
			FolderController.searchFolder(account, folder, query);
		},
		mailTo: function(params) {
			this._handleMailto(params);
		},
		showSetup: function() {
			this._navigate('setup');
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('navigation:hide');
			Radio.ui.trigger('setup:show');
		},
		showKeyboardShortcuts: function() {
			this._navigate('shortcuts');
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('keyboardShortcuts:show');
		},
		showAccountSettings: function(accountId) {
			this._navigate('accounts/' +  accountId + '/settings');
			var account = this.accounts.get(accountId);
			if (_.isUndefined(account)) {
				// Unknown account id -> redirect
				Radio.ui.trigger('error:show', t('mail', 'Invalid account'));
				this.default();
				return;
			}
			Radio.ui.trigger('composer:leave');
			Radio.ui.trigger('navigation:hide');
			Radio.ui.trigger('accountsettings:show', account);
		}
	};

	return RoutingController;
});
