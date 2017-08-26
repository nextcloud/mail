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

	var _ = require('underscore');
	var $ = require('jquery');
	var Marionette = require('backbone.marionette');
	var Radio = require('radio');
	var MessagesItemView = require('views/messagesitem');
	var MessageListTemplate = require('handlebars-loader!templates/message-list.html');
	var EmptyFolderView = require('views/emptyfolderview');
	var NoSearchResultView = require('views/nosearchresultmessagelistview');

	return Marionette.CompositeView.extend({
		collection: null,
		$scrollContainer: undefined,
		childView: MessagesItemView,
		childViewContainer: '#mail-message-list',
		template: MessageListTemplate,
		currentMessage: null,
		searchQuery: null,
		loadingMore: false,

		/**
		 * @private
		 * @type {bool}
		 */
		_reloaded: false,

		events: {
			DOMMouseScroll: 'onWheel',
			mousewheel: 'onWheel'
		},
		initialize: function(options) {
			this.searchQuery = options.searchQuery;

			var _this = this;
			this.on('dom:refresh', this._bindScrollEvents);
			Radio.ui.reply('messagesview:collection', function() {
				return _this.collection;
			});
			this.listenTo(Radio.ui, 'messagesview:messages:update', this.refresh);
			this.listenTo(Radio.ui, 'messagesview:filter', this.filterCurrentMailbox);
			this.listenTo(Radio.ui, 'messagesview:message:setactive', this.setActiveMessage);
			this.listenTo(Radio.message, 'messagesview:message:next', this.selectNextMessage);
			this.listenTo(Radio.message, 'messagesview:message:prev', this.selectPreviousMessage);
		},
		_bindScrollEvents: function() {
			this.$scrollContainer = this.$el.parent();
			this.$scrollContainer.scroll(_.bind(this.onScroll, this));
		},
		emptyView: function() {
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
		/**
		 * Set active class for current message and remove it from old one
		 *
		 * @param {Message} message
		 */
		setActiveMessage: function(message) {
			if (this.currentMessage !== null) {
				this.currentMessage.set('active', false);
			}

			this.currentMessage = message;
			if (message !== null) {
				message.set('active', true);
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
				var folder = nextMessage.folder;
				var account = folder.account;
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
				var folder = previousMessage.folder;
				var account = folder.account;
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
			this._syncMessages();
		},
		onScroll: function() {
			if (this._reloaded) {
				this._reloaded = false;
				return;
			}
			if (this.loadingMore === true) {
				// Ignore events until loading has finished
				return;
			}
			if (this.$scrollContainer.scrollTop() === 0) {
				// Scrolled to top -> refresh
				this.loadingMore = true;
				this._syncMessages();
				return;
			}
			if ((this.$scrollContainer.scrollTop() + this.$scrollContainer.height()) > (this.$el.height() - 150)) {
				// Scrolled all the way down -> load more
				this.loadingMore = true;
				this._loadNextMessages();
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
			this._syncMessages();
		},

		/**
		 * @private
		 * @returns {Promise}
		 */
		_loadNextMessages: function() {
			// Add loading feedback
			this.$('#load-more-mail-messages').addClass('icon-loading-small');

			var account = require('state').currentAccount;
			var folder = require('state').currentFolder;
			return Radio.message.request('next-page', account, folder, {
				filter: this.searchQuery || ''
			}).then(function() {
				Radio.ui.trigger('messagesview:message:setactive', require('state').currentMessage);
			}, function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
			}).then(function() {
				// Remove loading feedback again
				this.$('#load-more-mail-messages').removeClass('icon-loading-small');
				this.loadingMore = false;
				// Reload scrolls the list to the top, hence a unwanted
				// scroll event is fired, which we want to ignore
				this._reloaded = false;
			}.bind(this), console.error.bind(this));
		},

		/**
		 * @private
		 * @returns {Promise}
		 */
		_syncMessages: function() {
			// Loading feedback
			$('#mail-message-list-loading').css('opacity', 0)
				.slideDown('slow')
				.animate(
					{opacity: 1},
					{queue: false, duration: 'slow'}
				);

			var folder = require('state').currentFolder;
			return Radio.sync.request('sync:folder', folder)
				.catch(function(e) {
					console.error(e);
					Radio.ui.trigger('error:show', t('mail', 'Error while refreshing messages.'));
				})
				.then(function() {
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
									this.loadingMore = false;
								}.bind(this)
							});
					this._reloaded = true;
				}.bind(this), console.error.bind(this));
		},

		onBeforeRender: function() {
			// FF jump scrolls when we load more mesages. This stores the scroll
			// position before the element is re-rendered and restores it afterwards
			if (this.$scrollContainer) {
				this._prevScrollTop = this.$scrollContainer.scrollTop();
			}
		},
		onRender: function() {
			// see onBeforeRender
			if (this.$scrollContainer) {
				if (this._prevScrollTop) {
					this.$scrollContainer.scrollTop(this._prevScrollTop);
				}
			}
		}
	});
});
