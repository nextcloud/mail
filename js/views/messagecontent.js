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

	var Backbone = require('backbone');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var MessageView = require('views/message');
	var MessagesView = require('views/messages');
	var MessageContentTemplate = require('text!templates/messagecontent.html');

	return Marionette.LayoutView.extend({
		template: Handlebars.compile(MessageContentTemplate),
		className: 'container',
		regions: {
			messages: '#mail-messages',
			message: '#mail-message'
		},
		initialize: function() {
			this.listenTo(Radio.ui, 'message:show', this.onShowMessage);
			//TODO: this.listenTo(Radio.ui, 'message:loading', this.onMessageLoading);
		},
		onShow: function() {
			this.messages.show(new MessagesView());
		},
		onShowMessage: function(message) {
			// Temporarily disable new-message composer events
			Radio.ui.trigger('composer:events:undelegate');

			var messageModel = new Backbone.Model(message);
			this.message.show(new MessageView({model: messageModel}));

			Radio.ui.trigger('messagesview:messageflag:set', message.id, 'unseen', false);
		}
	});
});
