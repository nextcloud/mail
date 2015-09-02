/* global OC */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var document = require('domready');
	var Mail = require('app');

	/**
	 * Start the application
	 */
	Mail.start();

	Mail.UI.initializeInterface();

	// auto detect button handling
	$('#auto_detect_account').click(function(event) {
		event.preventDefault();
		$('#mail-account-name, #mail-address, #mail-password, #mail-setup-manual-toggle')
			.prop('disabled', true);
		$('#mail-imap-host, #mail-imap-port, #mail-imap-sslmode, #mail-imap-user, #mail-imap-password')
			.prop('disabled', true);
		$('#mail-smtp-host, #mail-smtp-port, #mail-smtp-sslmode, #mail-smtp-user, #mail-smtp-password')
			.prop('disabled', true);

		$('#auto_detect_account')
			.prop('disabled', true)
			.val(t('mail', 'Connecting …'));
		$('#connect-loading').fadeIn();
		var emailAddress = $('#mail-address').val();
		var accountName = $('#mail-account-name').val();
		var password = $('#mail-password').val();

		var dataArray = {
			accountName: accountName,
			emailAddress: emailAddress,
			password: password,
			autoDetect: true
		};

		// if manual setup is open, use manual values
		if ($('#mail-setup-manual').css('display') === 'block') {
			dataArray = {
				accountName: accountName,
				emailAddress: emailAddress,
				password: password,
				imapHost: $('#mail-imap-host').val(),
				imapPort: $('#mail-imap-port').val(),
				imapSslMode: $('#mail-imap-sslmode').val(),
				imapUser: $('#mail-imap-user').val(),
				imapPassword: $('#mail-imap-password').val(),
				smtpHost: $('#mail-smtp-host').val(),
				smtpPort: $('#mail-smtp-port').val(),
				smtpSslMode: $('#mail-smtp-sslmode').val(),
				smtpUser: $('#mail-smtp-user').val(),
				smtpPassword: $('#mail-smtp-password').val(),
				autoDetect: false
			};
		}

		$.ajax(OC.generateUrl('apps/mail/accounts'), {
			data: dataArray,
			type: 'POST',
			success: function() {
				// reload accounts
				Mail.trigger('accounts:load');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				switch (jqXHR.status) {
					case 400:
						var response = JSON.parse(jqXHR.responseText);
						Mail.UI.showError(t('mail', response.message));
						break;
					default:
						var error = errorThrown || textStatus || t('mail', 'Unknown error');
						Mail.UI.showError(t('mail', 'Error while creating an account: ' + error));
				}
			},
			complete: function() {
				$('#mail-account-name, #mail-address, #mail-password, #mail-setup-manual-toggle')
					.prop('disabled', false);
				$('#mail-imap-host, #mail-imap-port, #mail-imap-sslmode, #mail-imap-user, #mail-imap-password')
					.prop('disabled', false);
				$('#mail-smtp-host, #mail-smtp-port, #mail-smtp-sslmode, #mail-smtp-user, #mail-smtp-password')
					.prop('disabled', false);
				$('#auto_detect_account')
					.prop('disabled', false)
					.val(t('mail', 'Connect'));
				$('#connect-loading').hide();

				Mail.UI.showMenu();
			}
		});
	});

	// set standard port for the selected IMAP & SMTP security
	$(document).on('change', '#mail-imap-sslmode', function() {
		var imapDefaultPort = 143;
		var imapDefaultSecurePort = 993;

		switch ($(this).val()) {
			case 'none':
			case 'tls':
				$('#mail-imap-port').val(imapDefaultPort);
				break;
			case 'ssl':
				$('#mail-imap-port').val(imapDefaultSecurePort);
				break;
		}
	});

	$(document).on('change', '#mail-smtp-sslmode', function() {
		var smtpDefaultPort = 587;
		var smtpDefaultSecurePort = 465;

		switch ($(this).val()) {
			case 'none':
			case 'tls':
				$('#mail-smtp-port').val(smtpDefaultPort);
				break;
			case 'ssl':
				$('#mail-smtp-port').val(smtpDefaultSecurePort);
				break;
		}
	});

	// toggle for advanced account configuration
	$(document).on('click', '#mail-setup-manual-toggle', function() {
		Mail.UI.toggleManualSetup();
	});

	// new mail message button handling
	$(document).on('click', '#mail_new_message', Mail.UI.openComposer);

	/**
	 * Detects pasted text by browser plugins, and other software.
	 * Check for changes in message bodies every second.
	 */
	setInterval((function() {
		// Begin the loop.
		return function() {

			// Define which elements hold the message body.
			var MessageBody = $('.message-body');

			/**
			 * If the message body is displayed and has content:
			 * Prepare the message body content for processing.
			 * If there is new message body content to process:
			 * Resize the text area.
			 * Toggle the send button, based on whether the message is ready or not.
			 * Prepare the new message body content for future processing.
			 */
			if (MessageBody.val()) {
				var OldMessageBody = MessageBody.val();
				var NewMessageBody = MessageBody.val();
				if (NewMessageBody !== OldMessageBody) {
					MessageBody.trigger('autosize.resize');
					OldMessageBody = NewMessageBody;
				}
			}
		};
	})(), 1000);

	$(document).on('click', '#forward-button', function() {
		Mail.UI.openForwardComposer();
	});

	$(document).on('click', '#mail-message .attachment-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().data('messageId');
		var attachmentId = $(this).parent().data('attachmentId');
		Mail.UI.saveAttachment(messageId, attachmentId);
	});

	$(document).on('click', '#mail-message .attachments-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).data('messageId');
		Mail.UI.saveAttachment(messageId);
	});

	$(document).on('click', '.link-mailto', function(event) {
		Mail.UI.openComposer(event);
	});

	// close message when close button is tapped on mobile
	$(document).on('click', '#mail-message-close', function() {
		$('#mail-message').addClass('hidden-mobile');
	});

	$(document).on('show', function() {
		Mail.UI.changeFavicon(OC.filePath('mail', 'img', 'favicon.png'));
	});

	// Listens to key strokes, and executes a function based on the key combinations.
	$(document).keyup(function(event) {
		// Define which objects to check for the event properties.
		// (Window object provides fallback for IE8 and lower.)
		event = event || window.event;
		var key = event.keyCode || event.which;
		// If the client is currently viewing a message:
		if (Mail.State.currentMessageId) {
			switch (key) {
				// If delete key is pressed:
				case 46:
					// If not composing a reply:
					if (!$('.to, .cc, .message-body').is(':focus')) {
						// Mimic a client clicking the delete button for the currently active message.
						$('.mail_message_summary.active .icon-delete.action.delete').click();
					}
					break;
			}
		}
	});

	// Show the images if wanted
	$(document).on('click', '#show-images-button', function() {
		$('#show-images-text').hide();
		$('iframe').contents().find('img[data-original-src]').each(function() {
			$(this).attr('src', $(this).attr('data-original-src'));
		});
	});
});
