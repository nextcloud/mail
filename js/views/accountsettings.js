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
			'submitButton': 'input[type=submit]',
			'aliasName' : 'input[name="alias-name"]',
			'imapHost' : 'input[name="imap-host"]',
			'imapUser' : 'input[name="imap-user"]',
			'imapPort' : 'input[name="imap-port"]',
			'smtpHost' : 'input[name="smtp-host"]',
			'smtpUser' : 'input[name="smtp-user"]',
			'smtpPort' : 'input[name="smtp-port"]'
		},
		events: {
			'click @ui.submitButton': 'onSubmit',
			'submit @ui.form': 'onSubmit'
		},
		regions: {
			aliasesRegion : '#aliases-list'
		},
		initialize: function(options) {
			this.currentAccount = options.account;
		},
		onSubmit: function(e) {
			e.preventDefault();
			var alias = {
				'alias': this.ui.alias.val(),
				'name': this.ui.aliasName.val()
			};
			this.ui.alias.prop('disabled', true);
			this.ui.aliasName.prop('disabled', true);
			this.ui.submitButton.val('Saving');
			this.ui.submitButton.prop('disabled', true);
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
				_this.ui.submitButton.prop('disabled', false);
				_this.ui.submitButton.val('Save');
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
