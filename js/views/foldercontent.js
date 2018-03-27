/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');
	var Backbone = require('backbone');
	var Marionette = require('backbone.marionette');
	var Radio = require('radio');
	var ComposerView = require('views/composerview');
	var MessageView = require('views/messageview');
	var MessagesView = require('views/messagesview');
	var ErrorView = require('views/errorview');
	var LoadingView = require('views/loadingview');
	var MessageContentTemplate = require('templates/foldercontent.html');
	var EmptyMessageView = require('views/emptymessageview');

	var DetailView = Object.freeze({
		ERROR: -2,
		MESSAGE: 1,
		COMPOSER: 2
	});

	return Marionette.View.extend({
		template: MessageContentTemplate,
		className: 'container',
		detailView: null,
		account: null,
		folder: null,
		searchQuery: null,
		composer: null,
		regions: {
			messages: '#mail-messages',
			message: '#mail-message'
		},
		initialize: function(options) {
			this.account = options.account;
			this.folder = options.folder;
			this.searchQuery = options.searchQuery;

			this.listenTo(Radio.ui, 'message:show', this.onShowMessage);
			this.listenTo(Radio.ui, 'message:error', this.onShowError);
			this.listenTo(Radio.ui, 'composer:show', this.onShowComposer);
			this.listenTo(Radio.keyboard, 'keyup', this.onKeyUp);

			// TODO: check whether this code is still needed
			this.listenTo(Radio.ui, 'composer:events:undelegate', function() {
				if (this.composer) {
					this.composer.undelegateEvents();
				}
			});
			// END TODO

			this.listenTo(Radio.ui, 'message:loading', this.onMessageLoading);
			this.listenTo(Radio.ui, 'message:empty', this.onMessageEmpty);
		},
		onRender: function() {
			this.showChildView('messages', new MessagesView({
				collection: this.folder.messages,
				searchQuery: this.searchQuery
			}));
		},
		onShowMessage: function(message, body) {
			// Temporarily disable new-message composer events
			Radio.ui.trigger('composer:events:undelegate');

			var messageModel = new Backbone.Model(body);
			this.showChildView('message', new MessageView({
				account: this.account,
				folder: this.folder,
				message: message,
				model: messageModel
			}));
			this.detailView = DetailView.MESSAGE;
			this.markMessageAsRead(message);
		},
		markMessageAsRead: function(message) {
			// The message is not actually displayed on mobile when calling onShowMessage()
			// on mobiles then, we shall not mark the email as read until the user opened it
			var isMobile = $(window).width() < 768;
			if (isMobile === false) {
				Radio.message.trigger('flag', message, 'unseen', false);
			}
		},
		onShowError: function(errorMessage) {
			this.showChildView('message', new ErrorView({
				text: errorMessage
			}));
			this.detailView = DetailView.ERROR;
		},
		onShowComposer: function(data, isDraft) {
			isDraft = _.isUndefined(isDraft) ? false : isDraft;
			$('.tooltip').remove();
			$('#mail_new_message').prop('disabled', true);
			$('#mail-message').removeClass('hidden-mobile');

			// setup composer view
			this.showChildView('message', new ComposerView({
				accounts: require('state').accounts,
				data: data
			}));
			this.detailView = DetailView.COMPOSER;
			this.composer = this.getChildView('message');

			if (data && data.hasHtmlBody) {
				Radio.ui.trigger('error:show', t('mail', 'Opening HTML drafts is not supported yet.'));
			}

			if (!data.to.length) {
				// focus 'to' field automatically on clicking New message button
				this.composer.focusTo();
			} else if (!data.subject) {
				this.composer.focusSubject();
			} else {
				this.composer.focusMessageBody();
			}

			if (data && !_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).
				data().email)) {
				var to = '"' + $(data.currentTarget).
					data().label + '" <' + $(data.currentTarget).
					data().email + '>';
				this.composer.setTo(to);
				this.composer.focusSubject();
			}

			if (!isDraft) {
				Radio.ui.trigger('messagesview:message:setactive', null);
			}
		},
		onMessageLoading: function(text) {
			this.showChildView('message', new LoadingView({
				text: text
			}));
		},
		onMessageEmpty: function() {
			this.showChildView('message', new EmptyMessageView());
		},
		onKeyUp: function(event, key) {
			var message;
			var state;
			switch (key) {
				case 46:
					// Mimic a client clicking the delete button for the currently active message.
					$('.mail-message-summary.active .icon-delete.action.delete').click();
					break;
				case 39:
				case 74:
					// right arrow or 'j' -> next message
					event.preventDefault();
					Radio.message.trigger('messagesview:message:next');
					break;
				case 37:
				case 75:
					// left arrow or 'k' -> previous message
					event.preventDefault();
					Radio.message.trigger('messagesview:message:prev');
					break;
				case 67:
					// 'c' -> show new message composer
					event.preventDefault();
					Radio.ui.trigger('composer:show');
					break;
				case 82:
					// 'r' -> refresh list of messages
					event.preventDefault();
					Radio.ui.trigger('messagesview:messages:update');
					break;
				case 83:
					// 's' -> toggle star
					event.preventDefault();
					message = require('state').currentMessage;
					if (message) {
						state = message.get('flags').get('flagged');
						Radio.message.trigger('flag', message, 'flagged', !state);
					}
					break;
				case 85:
					// 'u' -> toggle unread
					event.preventDefault();
					message = require('state').currentMessage;
					if (message) {
						state = message.get('flags').get('unseen');
						Radio.message.trigger('flag', message, 'unseen', !state);
					}
					break;
			}
		}
	});
});
