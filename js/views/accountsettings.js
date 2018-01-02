/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

define(function(require) {
	'use strict';

	var Marionette = require('backbone.marionette');
	var AccountSettingsTemplate = require('templates/accountsettings.html');
	var AccountFormView = require('views/accountformview');
	var AliasesView = require('views/aliases');
	var Radio = require('radio');

	return Marionette.View.extend({
		template: AccountSettingsTemplate,
		templateContext: function() {
			var aliases = this.aliases;
			return {
				aliases: aliases,
				email: this.currentAccount.get('email')
			};
		},
		currentAccount: null,
		aliases: null,
		ui: {
			'form': 'form',
			'alias': 'input[name="alias"]',
			'submitButton': 'input[type=submit]',
			'aliasName': 'input[name="alias-name"]'
		},
		events: {
			'click @ui.submitButton': 'onSubmit',
			'submit @ui.form': 'onSubmit'
		},
		regions: {
			aliasesRegion: '#aliases-list',
			accountRegion: '#mail-settings'
		},
		initialize: function(options) {
			this.currentAccount = options.account;
		},
		onSubmit: function(e) {
			e.preventDefault();
			var alias = {
				alias: this.getUI('alias').val(),
				name: this.getUI('aliasName').val()
			};
			this.getUI('alias').prop('disabled', true);
			this.getUI('aliasName').prop('disabled', true);
			this.getUI('submitButton').val('Saving');
			this.getUI('submitButton').prop('disabled', true);
			var _this = this;

			Radio.aliases.request('save', this.currentAccount, alias)
				.then(function(data) {
					_this.currentAccount.get('aliases').add(data);
				}, console.error.bind(this))
				.then(function() {
					_this.getUI('alias').val('');
					_this.getUI('aliasName').val('');
					_this.getUI('alias').prop('disabled', false);
					_this.getUI('aliasName').prop('disabled', false);
					_this.getUI('submitButton').prop('disabled', false);
					_this.getUI('submitButton').val('Save');
				});
		},
		onRender: function() {
			this.showAliases();
			this.showAccountSetup();
		},
		showAliases: function() {
			this.showChildView('aliasesRegion', new AliasesView({
				currentAccount: this.currentAccount
			}));
		},

		showAccountSetup: function() {
			this.showChildView('accountRegion', new AccountFormView({
				config: this.currentAccount.attributes,
				settingsPage: true
			}));
		},

		startLoading: function() {
			OC.msg.startSaving('#mail-settings-msg');
			$('#mail-settings-loading').show();
		},

		/**
		 * @private
		 * @param {Object} account
		 * @returns {Promise}
		 */
		onChildviewFormSubmit: function(config) {
			config.accountId = this.currentAccount.get('accountId');

			this.startLoading();

			return Radio.account.request('update', config).then(function() {
				var response = {status: 'success', data: {message: t('mail', 'Saved')}};
				OC.msg.finishedSaving('#mail-settings-msg', response);
				$('#mail-settings-loading').hide();
			}).catch(function(error) {
				console.error('could not update account:', error);
				var response = {status: 'error', data: {message: error}};
				OC.msg.finishedSaving('#mail-settings-msg', response);
				$('#mail-settings-loading').hide();
			}).catch(console.error.bind(this));
		}
	});
});
