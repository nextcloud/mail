/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var AccountController = require('controller/accountcontroller');
	var Radio = require('radio');
	var SetupTemplate = require('text!templates/setup.html');

	return Marionette.View.extend({
		template: Handlebars.compile(SetupTemplate),
		id: 'setup',
		firstToggle: true,
		displayName: '',
		email: '',
		manualMode: false,
		loading: false,
		ui: {
			'form': 'form',
			'inputs': 'input, select',
			'toggleManualMode': '.toggle-manual-mode',
			'accountName': 'input[name="account-name"]',
			'mailAddress': 'input[name="mail-address"]',
			'mailPassword': 'input[name="mail-password"]',
			'manualInputs': '.manual-inputs',
			'imapHost': 'input[name="imap-host"]',
			'imapPort': 'input[name="imap-port"]',
			'imapSslMode': '#setup-imap-ssl-mode',
			'imapUser': 'input[name="imap-user"]',
			'imapPassword': 'input[name="imap-password"]',
			'smtpHost': 'input[name="smtp-host"]',
			'smtpSslMode': '#setup-smtp-ssl-mode',
			'smtpPort': 'input[name="smtp-port"]',
			'smtpUser': 'input[name="smtp-user"]',
			'smtpPassword': 'input[name="smtp-password"]',
			'submitButton': 'input[type=submit]',
			'iconLoading': '#connect-loading'
		},
		events: {
			'click @ui.submitButton': 'onSubmit',
			'submit @ui.form': 'onSubmit',
			'click @ui.toggleManualMode': 'toggleManualMode',
			'change @ui.imapSslMode': 'onImapSslModeChange',
			'change @ui.smtpSslMode': 'onSmtpSslModeChange'
		},
		initialize: function(options) {
			_.defaults(options, {
				displayName: '',
				email: ''
			});
			this.displayName = options.displayName;
			this.email = options.email;
		},
		onRender: function() {
			this.getUI('manualInputs').hide();
			this.getUI('iconLoading').hide();
			this.getUI('accountName').val(this.displayName);
			this.getUI('mailAddress').val(this.email);
		},
		toggleManualMode: function(e) {
			e.stopPropagation();
			this.manualMode = !this.manualMode;

			this.getUI('manualInputs').slideToggle();
			this.getUI('imapHost').focus();

			if (this.manualMode) {
				if (this.firstToggle) {
					// Manual mode opened for the first time
					// -> copy email, password for imap&smtp
					var email = this.getUI('mailAddress').val();
					var password = this.getUI('mailPassword').val();
					this.getUI('imapUser').val(email);
					this.getUI('imapPassword').val(password);
					this.getUI('smtpUser').val(email);
					this.getUI('smtpPassword').val(password);
					this.firstToggle = false;
				}

				var _this = this;
				this.getUI('mailPassword').slideToggle(function() {
					_this.getUI('mailAddress').parent()
						.removeClass('groupmiddle').addClass('groupbottom');

					// Focus imap host input
					_this.getUI('imapHost').focus();
				});
			} else {
				this.getUI('mailPassword').slideToggle();
				this.getUI('mailAddress').parent()
					.removeClass('groupbottom').addClass('groupmiddle');
			}
		},
		onSubmit: function(e) {
			e.preventDefault();
			e.stopPropagation();

			this.getUI('inputs').prop('disabled', true);
			this.getUI('submitButton').val(t('mail', 'Connecting'));
			this.getUI('iconLoading').fadeIn();

			var emailAddress = this.getUI('mailAddress').val();
			var accountName = this.getUI('accountName').val();
			var password = this.getUI('mailPassword').val();

			var config = {
				accountName: accountName,
				emailAddress: emailAddress,
				password: password,
				autoDetect: true
			};

			// if manual setup is open, use manual values
			if (this.manualMode) {
				config = {
					accountName: accountName,
					emailAddress: emailAddress,
					password: password,
					imapHost: this.getUI('imapHost').val(),
					imapPort: this.getUI('imapPort').val(),
					imapSslMode: this.getUI('imapSslMode').val(),
					imapUser: this.getUI('imapUser').val(),
					imapPassword: this.getUI('imapPassword').val(),
					smtpHost: this.getUI('smtpHost').val(),
					smtpPort: this.getUI('smtpPort').val(),
					smtpSslMode: this.getUI('smtpSslMode').val(),
					smtpUser: this.getUI('smtpUser').val(),
					smtpPassword: this.getUI('smtpPassword').val(),
					autoDetect: false
				};
			}

			this.loading = true;
			var creatingAccount = Radio.account.request('create', config);

			$.when(creatingAccount).done(function() {
				Radio.ui.trigger('navigation:show');
				Radio.ui.trigger('content:loading');
				// reload accounts
				$.when(AccountController.loadAccounts()).done(function(accounts) {
					// Let's assume there's at least one account after a successful
					// setup, so let's show the first one (could be the unified inbox)
					var firstAccount = accounts.first();
					var firstFolder = firstAccount.folders.first();
					Radio.navigation.trigger('folder', firstAccount.get('accountId'), firstFolder.get('id'));
				});
			});

			var _this = this;
			$.when(creatingAccount).fail(function(error) {
				_this.loading = false;
				Radio.ui.trigger('error:show', error);
				_this.getUI('iconLoading').hide();
				_this.getUI('inputs').prop('disabled', false);
				_this.getUI('submitButton').val(t('mail', 'Connect'));
			});
		},
		onImapSslModeChange: function() {
			// set standard port for the selected IMAP & SMTP security
			var imapDefaultPort = 143;
			var imapDefaultSecurePort = 993;

			switch (this.getUI('imapSslMode').val()) {
				case 'none':
				case 'tls':
					this.getUI('imapPort').val(imapDefaultPort);
					break;
				case 'ssl':
					this.getUI('imapPort').val(imapDefaultSecurePort);
					break;
			}
		},
		onSmtpSslModeChange: function() {
			var smtpDefaultPort = 587;
			var smtpDefaultSecurePort = 465;

			switch (this.getUI('smtpSslMode').val()) {
				case 'none':
				case 'tls':
					this.getUI('smtpPort').val(smtpDefaultPort);
					break;
				case 'ssl':
					this.getUI('smtpPort').val(smtpDefaultSecurePort);
					break;
			}
		}
	});
});
