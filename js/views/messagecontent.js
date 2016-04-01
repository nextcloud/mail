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
	var Backbone = require('backbone');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var ComposerView = require('views/composer');
	var MessageView = require('views/message');
	var MessagesView = require('views/messages');
	var LoadingView = require('views/loadingview');
	var MessageContentTemplate = require('text!templates/messagecontent.html');

	var DetailView = Object.freeze({
		MESSAGE: 1,
		COMPOSER: 2
	});

	return Marionette.LayoutView.extend({
		template: Handlebars.compile(MessageContentTemplate),
		className: 'container',
		detailView: null,
		composer: null,
		regions: {
			messages: '#mail-messages',
			message: '#mail-message'
		},
		initialize: function() {
			this.listenTo(Radio.ui, 'message:show', this.onShowMessage);
			this.listenTo(Radio.ui, 'composer:show', this.onShowComposer);
			this.listenTo(Radio.ui, 'composer:leave', this.onComposerLeave);

			// TODO: check whether this code is still needed
			this.listenTo(Radio.ui, 'composer:events:undelegate', function() {
				if (this.composer) {
					this.composer.undelegateEvents();
				}
			});
			// END TODO

			this.listenTo(Radio.ui, 'message:loading', this.onMessageLoading);
		},
		onShow: function() {
			this.messages.show(new MessagesView());
		},
		onShowMessage: function(message) {
			// Temporarily disable new-message composer events
			Radio.ui.trigger('composer:events:undelegate');

			var messageModel = new Backbone.Model(message);
			this.message.show(new MessageView({model: messageModel}));
			this.detailView = DetailView.MESSAGE;

			Radio.ui.trigger('messagesview:messageflag:set', message.id, 'unseen', false);
		},
		onShowComposer: function(data) {
			$('.tipsy').remove();
			$('#mail_new_message').prop('disabled', true);
			$('#mail-message').removeClass('hidden-mobile');

			// Abort message loads
			if (require('state').messageLoading !== null) {
				require('state').messageLoading.abort();
				$('iframe').parent().removeClass('icon-loading');
				$('#mail_message').removeClass('icon-loading');
			}

			// setup composer view
			this.message.show(new ComposerView({
				onSubmit: require('communication').sendMessage,
				onDraft: require('communication').saveDraft,
				accounts: require('state').accounts,
				data: data
			}));
			this.detailView = DetailView.COMPOSER;
			this.composer = this.message.currentView;

			if (data && data.hasHtmlBody) {
				Radio.ui.trigger('error:show', t('mail', 'Opening HTML drafts is not supported yet.'));
			}

			// set 'from' dropdown to current account
			// TODO: fix selector conflicts
			if (require('state').currentAccount.get('accountId') !== -1) {
				$('.mail-account').val(require('state').currentAccount.get('accountId'));
			}

			// focus 'to' field automatically on clicking New message button
			this.composer.focusTo();

			if (data && !_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).
				data().email)) {
				var to = '"' + $(data.currentTarget).
					data().label + '" <' + $(data.currentTarget).data().email + '>';
				this.composer.setTo(to);
				this.composer.focusSubject();
			}

			Radio.ui.trigger('messagesview:message:setactive', null);
		},
		onComposerLeave: function() {
			// TODO: refactor 'composer:leave' as it's buggy

			// Trigger only once
			if (this.detailView === DetailView.COMPOSER) {
				this.detailView = null;

				if (this.composer && this.composer.hasData === true) {
					if (this.composer.hasUnsavedChanges === true) {
						this.composer.saveDraft(function() {
							Radio.ui.trigger('notification:show', t('mail', 'Draft saved!'));
						});
					} else {
						Radio.ui.trigger('notification:show', t('mail', 'Draft saved!'));
					}
				}
			}
		},
		onMessageLoading: function() {
			this.message.show(new LoadingView());
		}
	});
});
