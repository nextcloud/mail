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

	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var _ = require('underscore');
	var $ = require('jquery');
	var Attachments = require('models/attachments');
	var HtmlHelper = require('util/htmlhelper');
	var ComposerView = require('views/composerview');
	var MessageAttachmentsView = require('views/messageattachments');
	var MessageTemplate = require('text!templates/message.html');

	return Marionette.View.extend({
		template: Handlebars.compile(MessageTemplate),
		className: 'mail-message-container',
		message: null,
		reply: null,
		account: null,
		folder: null,
		ui: {
			messageIframe: 'iframe'
		},
		regions: {
			replyComposer: '#reply-composer',
			attachments: '.mail-message-attachments'
		},
		initialize: function(options) {
			this.account = options.account;
			this.folder = options.folder;
			this.message = options.model;
			this.reply = {
				replyToList: this.message.get('replyToList'),
				replyCc: this.message.get('replyCc'),
				toEmail: this.message.get('toEmail'),
				replyCcList: this.message.get('replyCcList'),
				body: ''
			};

			// Add body content to inline reply (text mails)
			if (!this.message.get('hasHtmlBody')) {
				var date = new Date(this.message.get('dateIso'));
				var minutes = date.getMinutes();
				var text = HtmlHelper.htmlToText(this.message.get('body'));

				this.reply.body = '\n\n\n\n' +
					this.message.get('from') + ' – ' +
					$.datepicker.formatDate('D, d. MM yy ', date) +
					date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
					text.replace(/\n/g, '\n> ');
			}

			// Save current messages's content for later use (forward)
			if (!this.message.get('hasHtmlBody')) {
				require('state').currentMessageBody = this.message.get('body');
			}
			require('state').currentMessageSubject = this.message.get('subject');

			// Render the message body
			adjustControlsWidth();

			// Hide forward button until the message has finished loading
			if (this.message.get('hasHtmlBody')) {
				$('#forward-button').hide();
			}
		},
		onIframeLoad: function() {
			// Expand height to not have two scrollbars
			this.ui.messageIframe.height(this.ui.messageIframe.contents().find('html').height() + 20);
			// Fix styling
			this.ui.messageIframe.contents().find('body').css({
				'margin': '0',
				'font-weight': 'normal',
				'font-size': '.8em',
				'line-height': '1.6em',
				'font-family': '"Open Sans", Frutiger, Calibri, "Myriad Pro", Myriad, sans-serif',
				'color': '#000'
			});
			// Fix font when different font is forced
			this.ui.messageIframe.contents().find('font').prop({
				'face': 'Open Sans',
				'color': '#000'
			});
			this.ui.messageIframe.contents().find('.moz-text-flowed').css({
				'font-family': 'inherit',
				'font-size': 'inherit'
			});
			// Expand height again after rendering to account for new size
			this.ui.messageIframe.height(this.ui.messageIframe.contents().find('html').height() + 20);
			// Grey out previous replies
			this.ui.messageIframe.contents().find('blockquote').css({
				'color': '#888'
			});
			// Remove spinner when loading finished
			this.ui.messageIframe.parent().removeClass('icon-loading');

			// Does the html mail have blocked images?
			var hasBlockedImages = false;
			if (this.ui.messageIframe.contents().
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
			var text = this.ui.messageIframe.contents().find('body').html();
			text = HtmlHelper.htmlToText(text);
			var date = new Date(this.message.get('dateIso'));
			this.replyComposer.currentView.setReplyBody(this.message.get('from'), date, text);

			// Safe current mesages's content for later use (forward)
			require('state').currentMessageBody = text;

			// Show forward button
			this.$('#forward-button').show();
		},
		onRender: function() {
			this.ui.messageIframe.on('load', _.bind(this.onIframeLoad, this));

			this.showChildView('attachments', new MessageAttachmentsView({
				collection: new Attachments(this.message.get('attachments')),
				message: this.model
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
