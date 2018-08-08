/* global adjustControlsWidth */

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

	var Marionette = require('backbone.marionette');
	var _ = require('underscore');
	var $ = require('jquery');
	require('jquery-ui/ui/widgets/datepicker'); // formatDate
	var Attachments = require('models/attachments');
	var HtmlHelper = require('util/htmlhelper');
	var ComposerView = require('views/composerview');
	var MessageAttachmentsView = require('views/messageattachmentsview');
	var MessageTemplate = require('templates/message.html');
	var ReplyBuilder = require('replybuilder');

	return Marionette.View.extend({
		template: MessageTemplate,
		className: 'app-content-details',
		message: null,
		messageBody: null,
		reply: null,
		account: null,
		folder: null,
		ui: {
			messageIframe: 'iframe',
			options: '#options'
		},
		regions: {
			replyComposer: '#reply-composer',
			attachments: '.mail-message-attachments'
		},

		initialize: function(options) {
			this.account = options.account;
			this.folder = options.folder;
			this.message = options.message;
			this.messageBody = options.model;
			this.reply = ReplyBuilder.buildReply(this.message, this.messageBody);

			// Add body content to inline reply (text mails)
			if (!this.messageBody.get('hasHtmlBody')) {
				var date = new Date(this.messageBody.get('dateIso'));
				var minutes = date.getMinutes();
				var text = HtmlHelper.htmlToText(this.messageBody.get('body'));

				this.reply.body = '\n\n\n\n' +
						this.messageBody.get('from')[0].label + ' – ' +
						$.datepicker.formatDate('D, d. MM yy ', date) +
						date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
						text.replace(/\n/g, '\n> ');
			}

			// Save current messages's content for later use (forward)
			if (!this.messageBody.get('hasHtmlBody')) {
				require('state').currentMessageBody = this.messageBody.get('body');
			}
			require('state').currentMessageSubject = this.messageBody.get('subject');

			// Render the message body
			adjustControlsWidth();

			// Hide options until the message has finished loading
			if (this.messageBody.get('hasHtmlBody')) {
				$('#options').hide();
			}
		},
		onIframeLoad: function() {
			// Expand height to not have two scrollbars
			this.getUI('messageIframe').height(this.getUI('messageIframe').contents().find('html').height() + 20);
			// Fix styling
			this.getUI('messageIframe').contents().find('body').css({
				'margin': '0',
				'font-weight': 'normal',
				'font-size': '.8em',
				'line-height': '1.6em',
				'font-family': '"Open Sans", Frutiger, Calibri, "Myriad Pro", Myriad, sans-serif',
				'color': '#000'
			});
			// Fix font when different font is forced
			this.getUI('messageIframe').contents().find('font').prop({
				'face': 'Open Sans',
				'color': '#000'
			});
			this.getUI('messageIframe').contents().find('.moz-text-flowed').css({
				'font-family': 'inherit',
				'font-size': 'inherit'
			});
			// Expand height again after rendering to account for new size
			this.getUI('messageIframe').height(this.getUI('messageIframe').contents().find('html').height() + 20);
			// Grey out previous replies
			this.getUI('messageIframe').contents().find('blockquote').css({
				'color': '#888'
			});
			// Remove spinner when loading finished
			this.getUI('messageIframe').parent().removeClass('icon-loading');

			// Does the html mail have blocked images?
			var hasBlockedImages = false;
			if (this.getUI('messageIframe').contents().
					find('[data-original-src],[data-original-style]').length) {
				hasBlockedImages = true;
			}

			// Show/hide button to load images
			if (hasBlockedImages) {
				$('#show-images-text').show();
			} else {
				$('#show-images-text').hide();
			}

			// Add body content to inline reply (html mails)
			var text = this.getUI('messageIframe').contents().find('body').html();
			text = HtmlHelper.htmlToText(text);
			var date = new Date(this.messageBody.get('dateIso'));
			this.getChildView('replyComposer').setReplyBody(this.messageBody.get('from')[0], date, text);

			// Safe current mesages's content for later use (forward)
			require('state').currentMessageBody = text;

			// Show options
			$(this.getUI('options')).show();
		},
		onRender: function() {
			this.getUI('messageIframe').on('load', _.bind(this.onIframeLoad, this));

			this.showChildView('attachments', new MessageAttachmentsView({
				collection: new Attachments(this.messageBody.get('attachments')),
				message: this.message
			}));

			// setup reply composer view
			this.showChildView('replyComposer', new ComposerView({
				type: 'reply',
				accounts: require('state').accounts,
				account: this.account,
				folder: this.folder,
				repliedMessage: this.message,
				data: this.reply
			}));
		}
	});
});
