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

	var Marionette = require('marionette');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	var AppView = Marionette.LayoutView.extend({
		el: $('#app'),
		regions: {
			navigation: '#app-navigation',
			content: '#app-content',
			setup: '#setup'
		},
		events: {
			'click #mail_new_message': 'onNewMessageClick'
		},
		initialize: function() {
			this.bindUIElements();

			// Global event handlers:
			this.listenTo(Radio.notification, 'favicon:change', this.changeFavicon);
			this.listenTo(Radio.ui, 'error:show', this.showError);
			this.listenTo(Radio.ui, 'content:hide', this.hideContent);

			// Hide notification favicon when switching back from
			// another browser tab
			$(document).on('show', this.onDocumentShow);

			// Listens to key strokes, and executes a function based
			// on the key combinations.
			$(document).keyup(this.onKeyUp);
		},
		onDocumentShow: function(e) {
			e.preventDefault();
			Radio.notification.trigger('favicon:change', OC.filePath('mail', 'img', 'favicon.png'));
		},
		onKeyUp: function(e) {
			// Define which objects to check for the event properties.
			// (Window object provides fallback for IE8 and lower.)
			e = e || window.e;
			var key = e.keyCode || e.which;
			// If the client is currently viewing a message:
			if (require('state').currentMessageId) {
				if (key === 46) {
					// If delete key is pressed:
					// If not composing a reply
					// and message list is visible (not being in a settings dialog)
					// and if searchbox is not focused
					if (!$('.to, .cc, .message-body').is(':focus') &&
						$('#mail-messages').is(':visible') &&
						!$('#searchbox').is(':focus')) {
						// Mimic a client clicking the delete button for the currently active message.
						$('.mail-message-summary.active .icon-delete.action.delete').click();
					}
				}
			}
		},
		onNewMessageClick: function(e) {
			e.preventDefault();
			require('ui').openComposer();
		},
		render: function() {
			// This view doesn't need rendering
		},
		changeFavicon: function(src) {
			$('link[rel="shortcut icon"]').attr('href', src);
		},
		showError: function(message) {
			OC.Notification.showTemporary(message);
			$('#app-navigation')
				.removeClass('icon-loading');
			$('#app-content')
				.removeClass('icon-loading');
			$('#mail-message')
				.removeClass('icon-loading');
			$('#mail_message')
				.removeClass('icon-loading');
		},
		hideContent: function() {
			$('#mail-messages').addClass('hidden');
			$('#mail-message').addClass('hidden');
			$('#mail_new_message').addClass('hidden');
			$('#app-navigation').removeClass('icon-loading');
		}
	});

	return AppView;
});
