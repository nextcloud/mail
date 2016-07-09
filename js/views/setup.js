/**
 * ownCloud - Mail
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

	return Marionette.ItemView.extend({
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
		onShow: function() {
			this.ui.manualInputs.hide();
			this.ui.iconLoading.hide();
			this.ui.accountName.val(this.displayName);
			this.ui.mailAddress.val(this.email);
		},
		toggleManualMode: function(e) {
			e.stopPropagation();
			this.manualMode = !this.manualMode;

			this.ui.manualInputs.slideToggle();
			this.ui.imapHost.focus();

			if (this.manualMode) {
				if (this.firstToggle) {
					// Manual mode opened for the first time
					// -> copy email, password for imap&smtp
					var email = this.ui.mailAddress.val();
					var password = this.ui.mailPassword.val();
					this.ui.imapUser.val(email);
					this.ui.imapPassword.val(password);
					this.ui.smtpUser.val(email);
					this.ui.smtpPassword.val(password);
					this.firstToggle = false;
				}

				var _this = this;
				this.ui.mailPassword.slideToggle(function() {
					_this.ui.mailAddress.parent()
						.removeClass('groupmiddle').addClass('groupbottom');

					// Focus imap host input
					_this.ui.imapHost.focus();
				});
			} else {
				this.ui.mailPassword.slideToggle();
				this.ui.mailAddress.parent()
					.removeClass('groupbottom').addClass('groupmiddle');
			}
		},
		onSubmit: function(e) {
			e.preventDefault();
			e.stopPropagation();

			this.ui.inputs.prop('disabled', true);
			this.ui.submitButton.val(t('mail', 'Connecting'));
			this.ui.iconLoading.fadeIn();

			var emailAddress = this.ui.mailAddress.val();
			var accountName = this.ui.accountName.val();
			var password = this.ui.mailPassword.val();

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
					imapHost: this.ui.imapHost.val(),
					imapPort: this.ui.imapPort.val(),
					imapSslMode: this.ui.imapSslMode.val(),
					imapUser: this.ui.imapUser.val(),
					imapPassword: this.ui.imapPassword.val(),
					smtpHost: this.ui.smtpHost.val(),
					smtpPort: this.ui.smtpPort.val(),
					smtpSslMode: this.ui.smtpSslMode.val(),
					smtpUser: this.ui.smtpUser.val(),
					smtpPassword: this.ui.smtpPassword.val(),
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
					$('#app-navigation').removeClass('icon-loading');

					// Let's assume there's at least one account after a successful
					// setup, so let's show the first one (could be the unified inbox)
					var firstAccount = accounts.first();
					var firstFolder = firstAccount.get('folders').first();
					Radio.navigation.trigger('folder', firstAccount.get('accountId'), firstFolder.get('id'));
				});
			});

			$.when(creatingAccount).fail(function(error) {
				Radio.ui.trigger('error:show', error);
			});

			var _this = this;
			$.when(creatingAccount).always(function() {
				_this.loading = false;
				_this.ui.iconLoading.hide();
				_this.ui.inputs.prop('disabled', false);
				_this.ui.submitButton.val(t('mail', 'Connect'));
			});
		},
		onImapSslModeChange: function() {
			// set standard port for the selected IMAP & SMTP security
			var imapDefaultPort = 143;
			var imapDefaultSecurePort = 993;

			switch (this.ui.imapSslMode.val()) {
				case 'none':
				case 'tls':
					this.ui.imapPort.val(imapDefaultPort);
					break;
				case 'ssl':
					this.ui.imapPort.val(imapDefaultSecurePort);
					break;
			}
		},
		onSmtpSslModeChange: function() {
			var smtpDefaultPort = 587;
			var smtpDefaultSecurePort = 465;

			switch (this.ui.smtpSslMode.val()) {
				case 'none':
				case 'tls':
					this.ui.smtpPort.val(smtpDefaultPort);
					break;
				case 'ssl':
					this.ui.smtpPort.val(smtpDefaultSecurePort);
					break;
			}
		}
	});
});
