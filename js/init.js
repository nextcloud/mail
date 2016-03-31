/* global Notification, SearchProxy */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var document = require('domready');
	var OC = require('OC');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var Mail = require('app');

	setUpMarionette();
	registerProtocolHandler();
	requestNotificationPermissions();
	setUpSearch();

	Mail.start();

	function setUpMarionette() {
		Marionette.TemplateCache.prototype.compileTemplate = function(rawTemplate) {
			return Handlebars.compile(rawTemplate);
		};
		Marionette.ItemView.prototype.modelEvents = {'change': 'render'};
		Marionette.CompositeView.prototype.modelEvents = {'change': 'render'};
	}

	function registerProtocolHandler() {
		if (window.navigator.registerProtocolHandler) {
			var url = window.location.protocol + '//' +
				window.location.host +
				OC.generateUrl('apps/mail/compose?uri=%s');
			try {
				window.navigator
					.registerProtocolHandler('mailto', url, 'ownCloud Mail');
			} catch (e) {
			}
		}
	}

	function requestNotificationPermissions() {
		// request permissions
		if (typeof Notification !== 'undefined') {
			Notification.requestPermission();
		}
	}

	function setUpSearch() {
		SearchProxy.setFilter(require('search').filter);
	}

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

	// TODO: create marionette view and encapsulate events
	$(document).on('click', '#forward-button', function() {
		require('ui').openForwardComposer();
	});

	// TODO: create marionette view and encapsulate events
	$(document).on('click', '#mail-message .attachment-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().data('messageId');
		var attachmentId = $(this).parent().data('attachmentId');
		require('ui').saveAttachment(messageId, attachmentId);
	});

	// TODO: create marionette view and encapsulate events
	$(document).on('click', '#mail-message .attachments-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).data('messageId');
		require('ui').saveAttachment(messageId);
	});

	$(document).on('click', '.link-mailto', function(event) {
		Radio.ui.trigger('composer:show', event);
	});

	// TODO: create marionette view and encapsulate events
	// close message when close button is tapped on mobile
	$(document).on('click', '#mail-message-close', function() {
		$('#mail-message').addClass('hidden-mobile');
	});

	// TODO: create marionette view and encapsulate events
	// Show the images if wanted
	$(document).on('click', '#show-images-button', function() {
		$('#show-images-text').hide();
		$('iframe').contents().find('img[data-original-src]').each(function() {
			$(this).attr('src', $(this).attr('data-original-src'));
			$(this).show();
		});
		$('iframe').contents().find('[data-original-style]').each(function() {
			$(this).attr('style', $(this).attr('data-original-style'));
		});
	});
});
