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
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var MessagesItemView = require('views/messagesitem');
	var MessageListTemplate = require('text!templates/message-list.html');
	var EmptyFolderView = require('views/emptyfolderview');
	var NoSearchResultView = require('views/nosearchresultmessagelistview');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		$scrollContainer: undefined,
		childView: MessagesItemView,
		childViewContainer: '#mail-message-list',
		template: Handlebars.compile(MessageListTemplate),
		currentMessage: null,
		searchQuery: null,
		loadingMore: false,
		reloaded: false,
		events: {
			DOMMouseScroll: 'onWheel',
			mousewheel: 'onWheel'
		},
		collectionEvents: {
			update: 'render'
		},
		initialize: function(options) {
			this.searchQuery = options.searchQuery;

			var _this = this;
			Radio.ui.reply('messagesview:collection', function() {
				return _this.collection;
			});
			this.listenTo(Radio.ui, 'messagesview:messages:update', this.refresh);
			this.listenTo(Radio.ui, 'messagesview:messageflag:set', this.setMessageFlag);
			this.listenTo(Radio.ui, 'messagesview:filter', this.filterCurrentMailbox);
			this.listenTo(Radio.ui, 'messagesview:message:setactive', this.setActiveMessage);
			this.listenTo(Radio.message, 'messagesview:message:next', this.selectNextMessage);
			this.listenTo(Radio.message, 'messagesview:message:prev', this.selectPreviousMessage);
		},
		onShow: function() {
			this.$scrollContainer = this.$el.parent();
			this.$scrollContainer.scroll(_.bind(this.onScroll, this));
		},
		getEmptyView: function() {
			if (this.searchQuery && this.searchQuery !== '') {
				return NoSearchResultView;
			} else {
				return EmptyFolderView;
			}
		},
		emptyViewOptions: function() {
			return {
				searchQuery: this.searchQuery
			};
		},
		setMessageFlag: function(messageId, flag, val) {
			var message = this.collection.get(messageId);
			if (message) {
				// TODO: globals are bad :-/
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;

				Radio.message.trigger('flag', account, folder, message, flag, val);
			}
		},
		/**
		 * Set active class for current message and remove it from old one
		 *
		 * @param {Message} message
		 */
		setActiveMessage: function(message) {
			var oldMessage = null;
			if (this.currentMessage !== null) {
				// TODO: make sure objects exist only once and compare references instead
				oldMessage = this.collection.get(this.currentMessage.get('id'));
				if (oldMessage) {
					oldMessage.set('active', false);
				}
			}

			this.currentMessage = message;
			if (message !== null) {
				message = this.collection.get(this.currentMessage);
				if (message) {
					message.set('active', true);
				}
			}

			require('state').currentMessage = message;
			Radio.ui.trigger('title:update');
		},
		selectNextMessage: function() {
			if (this.currentMessage === null) {
				return;
			}

			var message = this.collection.get(this.currentMessage);
			if (message === null) {
				return;
			}

			if (this.collection.indexOf(message) === (this.collection.length - 1)) {
				// Last message, nothing to do
				return;
			}

			var nextMessage = this.collection.at(this.collection.indexOf(message) + 1);
			if (nextMessage) {
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;
				Radio.message.trigger('load', account, folder, nextMessage, {
					force: true
				});
			}
		},
		selectPreviousMessage: function() {
			if (this.currentMessage === null) {
				return;
			}

			var message = this.collection.get(this.currentMessage);
			if (message === null) {
				return;
			}

			if (this.collection.indexOf(message) === 0) {
				// First message, nothing to do
				return;
			}

			var previousMessage = this.collection.at(this.collection.indexOf(message) - 1);
			if (previousMessage) {
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;
				Radio.message.trigger('load', account, folder, previousMessage, {
					force: true
				});
			}
		},
		refresh: function() {
			if (!require('state').currentAccount) {
				return;
			}
			if (!require('state').currentFolder) {
				return;
			}
			this.loadMessages(true);
		},
		loadMore: function() {
			this.loadMessages(false);
		},
		onScroll: function() {
			if (this.reloaded) {
				this.reloaded = false;
				return;
			}
			if (this.loadingMore === true) {
				// Ignore events until loading has finished
				return;
			}
			if (this.$scrollContainer.scrollTop() === 0) {
				// Scrolled to top -> refresh
				this.loadingMore = true;
				this.loadMessages(true);
				return;
			}
			if ((this.$scrollContainer.scrollTop() + this.$scrollContainer.height()) > (this.$el.height() - 150)) {
				// Scrolled all the way down -> load more
				this.loadingMore = true;
				this.loadMessages(false);
				return;
			}
		},
		onWheel: function(event) {
			if (event.originalEvent.wheelDelta && event.originalEvent.wheelDelta > 0) {
				// Scrolling up in non-FF browsers
				this.onScroll();
			} else if (event.originalEvent.detail && event.originalEvent.detail < 0) {
				// Scrolling up in FF
				this.onScroll();
			}
		},
		filterCurrentMailbox: function(query) {
			this.filterCriteria = {
				text: query
			};
			this.loadMessages(true);
		},
		loadMessages: function(reload) {
			reload = reload || false;
			var from = this.collection.size();
			if (reload) {
				from = 0;
			}
			// Add loading feedback
			$('#load-more-mail-messages').addClass('icon-loading-small');
			if (reload) {
				$('#mail-message-list-loading').css('opacity', 0)
					.slideDown('slow')
					.animate(
						{ opacity: 1 },
						{ queue: false, duration: 'slow' }
					);
			}

			var _this = this;
			var loadingMessages = Radio.message.request('entities',
				require('state').currentAccount,
				require('state').currentFolder,
				{
					from: from,
					to: from + 20,
					force: true,
					filter: this.searchQuery || '',
					// Replace cached message list on reload
					replace: reload
				});

			$.when(loadingMessages).done(function() {
				Radio.ui.trigger('messagesview:message:setactive', require('state').currentMessage);
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
				$('#load-more-mail-messages').removeClass('icon-loading-small');
				if (reload) {
					$('#mail-message-list-loading').css('opacity', 1)
						.slideUp('slow')
						.animate(
							{
								opacity: 0
							},
							{
								queue: false,
								duration: 'slow',
								complete: function() {
									_this.loadingMore = false;
								},
							});
				} else {
					_this.loadingMore = false;
				}
				// Reload scrolls the list to the top, hence a unwanted
				// scroll event is fired, which we want to ignore
				_this.reloaded = reload;
			});
		}
	});
});
