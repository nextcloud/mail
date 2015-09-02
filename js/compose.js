/* global Handlebars, views, OC */

/* This was a copy of composer.js for mailto links, see:
*
* TODO: https://github.com/owncloud/mail/issues/1020
*
* */

var Mail = {
	State:{
		accounts: null
	},
	UI:{
		initializeInterface:function() {

			$.ajax(OC.generateUrl('apps/mail/accounts'), {
				data:{},
				type:'GET',
				success:function(accounts) {
					Mail.State.accounts = accounts;

					// don't try to load accounts if there are none
					if (accounts.length === 0) {
						return;
					}
					// only show account switcher when there are multiple
					if (accounts.length > 1) {
						var source   = $('#mail-account-manager').html();
						var template = Handlebars.compile(source);
						var html = template(accounts);
						$('#accountManager').html(html);
					}

					// setup composer view
					var view = new views.Composer({
						el: $('#app-content'),
						onSubmit: function(accountId, message, options) {
							/**
							 * ATTENTION: this is a copy of mail.js Mail.Communication.sendMessage
							 */
							var defaultOptions = {
								success: function() {},
								error: function() {},
								complete: function() {},
								accountId: null,
								folderId: null,
								messageId: null,
								draftUID: null
							};
							_.defaults(options, defaultOptions);
							var url = OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: accountId});
							var data = {
								type: 'POST',
								success: options.success,
								error: options.error,
								complete: options.complete,
								data: {
									to: message.to,
									cc: message.cc,
									bcc: message.bcc,
									subject: message.subject,
									body: message.body,
									attachments: message.attachments,
									accountId: options.accountId,
									draftUID : options.draftUID
								}
							};
							$.ajax(url, data);
						},
						onDraft: function(accountId, message, options) {
							var defaultOptions = {
								success: function() {},
								error: function() {},
								complete: function() {},
								accountId: null,
								folderId: null,
								messageId: null,
								draftUID: null
							};
							_.defaults(options, defaultOptions);
							var url = OC.generateUrl('/apps/mail/accounts/{accountId}/draft', {accountId: accountId});
							var data = {
								type: 'POST',
								success: function(data) {
									options.success(data);
								},
								error: options.error,
								complete: options.complete,
								data: {
									to: message.to,
									cc: message.cc,
									bcc: message.bcc,
									subject: message.subject,
									body: message.body,
									attachments: message.attachments,
									accountId: options.accountId,
									folderId: options.folderId,
									messageId: options.messageId,
									uid : options.draftUID
								}
							};
							$.ajax(url, data);
						},
						onSent: function() {
							// TODO: fix selector conflicts
							$('#nav-buttons').removeClass('hidden');
							$('.mail-account').slideUp();
							$('.composer-fields').slideUp();
							$('#new-message-attachments').slideUp();
						},
						aliases: Mail.State.accounts
					});

					// And render it
					view.render({
						data: {
							to: $('#app').data('mailto')
						}
					});
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading the accounts.'));
				}
			});
		},

		showError: function(message) {
			OC.Notification.show(message);
			$('#app-navigation')
				.removeClass('icon-loading');
			$('#app-content')
				.removeClass('icon-loading');
		},

		hideMenu:function() {
			// TODO: fix selector conflicts
			var menu = $('#new-message');
			menu.addClass('hidden');
		}

	}
};

$(document).ready(function() {
	Mail.UI.initializeInterface();

	$(document).on('click', '#nav-to-mail', function(event) {
		event.stopPropagation();
		location.href = OC.generateUrl('/apps/mail/');
	});

	$(document).on('click', '#back-in-time', function(event) {
		event.stopPropagation();
		window.history.back();
	});

	// TODO: fix selector conflicts
	if ($('.cc').attr('value') || $('.bcc').attr('value')) {
		$('.composer-cc-bcc').show();
		$('.composer-cc-bcc-toggle').hide();
	}

});
