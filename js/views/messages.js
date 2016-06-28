/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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
	var Backbone = require('backbone');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var MessageCollection = require('models/messagecollection');
	var MessagesItemView = require('views/messagesitem');
	var MessageListTemplate = require('text!templates/message-list.html');
	var NoSearchResultMessageListView = require('views/nosearchresultmessagelistview');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		$scrollContainer: undefined,
		childView: MessagesItemView,
		childViewContainer: '#mail-message-list',
		template: Handlebars.compile(MessageListTemplate),
		currentMessageId: null,
		loadingMore: false,
		events: {
			'click #load-new-mail-messages': 'loadNew',
			'click #load-more-mail-messages': 'loadMore',
		},
		filterCriteria: null,
		initialize: function() {
			this.listenTo(this.collection, 'change:flags', this.changeFlags);

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
		onShow: function() {
			this.$scrollContainer = this.$el.parent();
			this.$scrollContainer.scroll(_.bind(this.onScroll, this));
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
			if (!require('state').currentAccount) {
				return;
			}
			if (!require('state').currentFolder) {
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
		onScroll: function() {
			if (this.loadingMore === true) {
				// Ignore events until loading has finished
				return;
			}
			if ((this.$scrollContainer.scrollTop() + this.$scrollContainer.height()) > (this.$el.height() - 150)) {
				this.loadingMore = true;
				this.loadMessages(false);
			}
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
			$('#load-more-mail-messages').addClass('icon-loading');

			var _this = this;
			var loadingMessages = Radio.message.request('entities',
				require('state').currentAccount,
				require('state').currentFolder,
				{
					from: from,
					to: from + 20,
					filter: this.filterCriteria ? this.filterCriteria.text : null,
					force: true,
					// Replace cached message list on reload
					replace: reload
				});

			$.when(loadingMessages).done(function(jsondata) {
				Radio.ui.trigger('messagesview:message:setactive', require('state').currentMessageId);
			});

			$.when(loadingMessages).fail(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
				// Set the old folder as being active
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;
				Radio.folder.trigger('setactive', account, folder);
			});

			$.when(loadingMessages).always(function() {
				// Remove loading feedback again
				$('#load-more-mail-messages').removeClass('icon-loading');
				$('#load-new-mail-messages')
					.removeClass('icon-loading-small')
					.val(t('mail', 'Check messages'))
					.prop('disabled', false);
				_this.loadingMore = false;
			});
		},
		addMessages: function(message) {
			var _this = this;
			// TODO: merge?
			message.each(function(msg) {
				_this.collection.add(msg);
			})
		},
		reset: function() {
			this.collection.reset();

			$('#messages-loading').fadeIn();
		}
	});
});
