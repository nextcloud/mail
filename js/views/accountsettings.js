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

	var $ = require('jquery');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var AccountSettingsTemplate = require('text!templates/accountsettings.html');
	var AliasesView = require('views/aliases');
	var Radio = require('radio');

	return Marionette.LayoutView.extend({
		template: Handlebars.compile(AccountSettingsTemplate),
		templateHelpers: function() {
			var aliases = this.aliases;
			return {
				aliases: aliases,
				email: this.currentAccount.get('email'),
				imapHost: this.currentAccount.get('imapHost'),
				imapUser: this.currentAccount.get('imapUser'),
				imapPort: this.currentAccount.get('imapPort'),
				smtpHost: this.currentAccount.get('smtpHost'),
				smtpUser: this.currentAccount.get('smtpUser'),
				smtpPort: this.currentAccount.get('smtpPort')
			};
		},
		currentAccount: null,
		aliases: null,
		ui: {
			'form': 'form',
			'alias': 'input[name="alias"]',
			'submitAliasButton': 'input[name="submit-alias"]',
			'aliasName' : 'input[name="alias-name"]',
			'imapHost' : 'input[name="imap-host"]',
			'imapUser' : 'input[name="imap-user"]',
			'imapPort' : 'input[name="imap-port"]',
			'imapPassword' : 'input[name="imap-password"]',
			'smtpHost' : 'input[name="smtp-host"]',
			'smtpUser' : 'input[name="smtp-user"]',
			'smtpPort' : 'input[name="smtp-port"]',
			'smtpPassword' : 'input[name="smtp-password"]',
			'submitAccountButton': 'input[name="submit-account-settings"]',
		},
		events: {
			'click @ui.submitAliasButton': 'onAliasSubmit',
			'click @ui.submitAccountSettingsButton': 'onAccountSubmit'
		},
		regions: {
			aliasesRegion : '#aliases-list'
		},
		initialize: function(options) {
			this.currentAccount = options.account;
		},

		onAccountSubmit: function(e){
			e.preventDefault();
			var config = {
				'imapHost': this.ui.imapHost.val(),
				'imapUser': this.ui.imapUser.val(),
				'imapPort': this.ui.imapPort.val(),
				'imapPasswort': this.ui.imapPasswort.val(),
				'smtpHost': this.ui.smtpHost.val(),
				'smtpUser': this.ui.smtpUser.val(),
				'smtpPort': this.ui.smtpPort.val(),
				'smtpPasswort': this.ui.smtpPasswort.val(),
			};
			this.ui.submitAccountSettingsButton.val('Saving');
			var _this = this;

			var savingAccount = Radio.account.request('edit', this.currentAccount, config);
			$.when(savingAccount).done(function(data) {
				_this.currentAccount.get('aliases').add(data);
			});

			$.when(savingAlias).always(function() {
				_this.ui.submitAccountSettingsButton.val('Save');
			});

		},
		onAliasSubmit: function(e) {
			e.preventDefault();
			var alias = {
				'alias': this.ui.alias.val(),
				'name': this.ui.aliasName.val()
			};
			this.ui.alias.prop('disabled', true);
			this.ui.aliasName.prop('disabled', true);
			this.ui.submitAliasButton.val('Saving');
			this.ui.submitAliasButton.prop('disabled', true);
			var _this = this;

			var savingAlias = Radio.aliases.request('save:alias', this.currentAccount, alias);
			$.when(savingAlias).done(function(data) {
				_this.currentAccount.get('aliases').add(data);
			});

			$.when(savingAlias).always(function() {
				_this.ui.alias.val('');
				_this.ui.aliasName.val('');
				_this.ui.alias.prop('disabled', false);
				_this.ui.aliasName.prop('disabled', false);
				_this.ui.submitAliasButton.prop('disabled', false);
				_this.ui.submitAliasButton.val('Save');
			});

		},
		onShow: function() {
			this.showAliases();
		},
		showAliases: function() {
			this.aliasesRegion.show(new AliasesView({
				currentAccount: this.currentAccount
			}));
		}
	});
});
