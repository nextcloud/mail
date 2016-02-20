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

	var $ = require('jquery');
	var Backbone = require('backbone');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var MessageCollection = require('models/messagecollection');
	var MessagesItemView = require('views/messagesitem');
	var MessageListTemplate = require('text!templates/message-list.html');
	var NoSearchResultMessageListView = require('views/nosearchresultmessagelistview');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		childView: MessagesItemView,
		childViewContainer: '#mail-message-list',
		template: Handlebars.compile(MessageListTemplate),
		currentMessageId: null,
		events: {
			'click #load-new-mail-messages': 'loadNew',
			'click #load-more-mail-messages': 'loadMore'
		},
		filterCriteria: null,
		initialize: function() {
			this.collection = new MessageCollection();
			this.collection.on('change:flags', this.changeFlags, this);

			var _this = this;
			Radio.ui.reply('messagesview:collection', function() {
				return _this.collection;
			});
			this.listenTo(Radio.ui, 'messagesview:messages:update', this.loadNew);
			this.listenTo(Radio.ui, 'messagesview:messages:reset', this.reset);
			this.listenTo(Radio.ui, 'messagesview:messages:add', this.addMessages);
			this.listenTo(Radio.ui, 'messagesview:messageflag:set', this.setMessageFlag);
			this.listenTo(Radio.ui, 'messagesview:filter', this.filterCurrentMailbox);
			this.listenTo(Radio.ui, 'messagesview:filter:clear', this.clearFilter);
			this.listenTo(Radio.ui, 'messagesview:message:setactive', this.setActiveMessage);
		},
		getEmptyView: function() {
			if (this.filterCriteria) {
				return NoSearchResultMessageListView;
			}
		},
		emptyViewOptions: function() {
			return {filterCriteria: this.filterCriteria};
		},
		changeFlags: function(model) {
			var unseen = model.get('flags').get('unseen');
			var prevUnseen = model.get('flags')._previousAttributes.unseen;
			//if(_.isUndefined(model._previousAttributes.flags.unseen)) {
			//	prevUnseen = model._previousAttributes.flags.get('unseen');
			//}
			if (unseen !== prevUnseen) {
				this.trigger('change:unseen', model, unseen);
			}
		},
		setMessageFlag: function(messageId, flag, val) {
			var message = this.collection.get(messageId);
			if (message) {
				message.flagMessage(flag, val);
			}
		},
		setActiveMessage: function(messageId) {
			// Set active class for current message and remove it from old one

			var message = null;
			if (this.currentMessageId !== null) {
				message = this.collection.get(this.currentMessageId);
				if (message) {
					message.set('active', false);
				}
			}

			this.currentMessageId = messageId;

			if (messageId !== null) {
				message = this.collection.get(this.currentMessageId);
				if (message) {
					message.set('active', true);
				}
			}

			require('state').currentMessageId = messageId;
			require('state').folderView.updateTitle();

		},
		loadNew: function() {
			if (!require('state').currentAccountId) {
				return;
			}
			if (!require('state').currentFolderId) {
				return;
			}
			// Add loading feedback
			$('#load-new-mail-messages')
				.addClass('icon-loading-small')
				.val(t('mail', 'Checking messages'))
				.prop('disabled', true);

			this.loadMessages(true);
		},
		loadMore: function() {
			this.loadMessages(false);
		},
		filterCurrentMailbox: function(query) {
			this.filterCriteria = {
				text: query
			};
			this.loadNew();
		},
		clearFilter: function() {
			$('#searchbox').val('');
			this.filterCriteria = null;
		},
		loadMessages: function(reload) {
			reload = reload || false;
			var from = this.collection.size();
			if (reload) {
				from = 0;
			}
			// Add loading feedback
			$('#load-more-mail-messages')
				.addClass('icon-loading-small')
				.val(t('mail', 'Loading …'))
				.prop('disabled', true);

			var _this = this;
			require('communication').fetchMessageList(
				require('state').currentAccountId,
				require('state').currentFolderId,
				{
					from: from,
					to: from + 20,
					filter: this.filterCriteria ? this.filterCriteria.text : null,
					force: true,
					// Replace cached message list on reload
					replace: reload,
					onSuccess: function(jsondata) {
						if (reload) {
							_this.collection.reset();
						}
						// Add messages
						_this.collection.add(jsondata);

						$('#app-content').removeClass('icon-loading');

						Radio.ui.trigger('messagesview:message:setactive', require('state').currentMessageId);
					},
					onError: function() {
						Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
						// Set the old folder as being active
						var accountId = require('state').currentAccountId;
						var folderId = require('state').currentFolderId;
						Radio.folder.trigger('setactive', accountId, folderId);
					},
					onComplete: function() {
						// Remove loading feedback again
						$('#load-more-mail-messages')
							.removeClass('icon-loading-small')
							.val(t('mail', 'Load more …'))
							.prop('disabled', false);
						$('#load-new-mail-messages')
							.removeClass('icon-loading-small')
							.val(t('mail', 'Check messages'))
							.prop('disabled', false);
					}
				});
		},
		addMessages: function(data) {
			this.collection.add(data);
		},
		reset: function() {
			this.collection.reset();

			$('#messages-loading').fadeIn();

			// TODO: add event
			$('#mail-message')
				.html('')
				.addClass('icon-loading');
		}
	});
});
