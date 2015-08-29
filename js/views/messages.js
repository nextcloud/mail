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

	var Backbone = require('backbone'),
		MessageCollection = require('models/messagecollection'),
		MessageView = require('views/message'),
		NoSearchResultMessageListView = require('views/nosearchresultmessagelistview');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		childView: MessageView,
		childViewContainer: '#mail-message-list',
		currentMessageId: null,
		events: {
			"click #load-new-mail-messages": "loadNew",
			"click #load-more-mail-messages": "loadMore"
		},
		filterCriteria: null,
		template: "#message-list-template",
		initialize: function() {
			this.collection = new MessageCollection();
			this.collection.on('change:flags', this.changeFlags, this);
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

		},
		loadNew: function() {
			if (!require('app').State.currentAccountId) {
				return;
			}
			if (!require('app').State.currentFolderId) {
				return;
			}
			// Add loading feedback
			$('#load-new-mail-messages')
				.addClass('icon-loading-small')
				.val(t('mail', 'Checking messages …'))
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

			var self = this;
			require('app').Communication.fetchMessageList(
				require('app').State.currentAccountId,
				require('app').State.currentFolderId,
				{
					from: from,
					to: from + 20,
					filter: this.filterCriteria ? this.filterCriteria.text : null,
					force: true,
					// Replace cached message list on reload
					replace: reload,
					onSuccess: function(jsondata) {
						if (reload) {
							self.collection.reset();
						}
						// Add messages
						self.collection.add(jsondata);

						$('#app-content').removeClass('icon-loading');

						require('app').UI.setMessageActive(require('app').State.currentMessageId);
					},
					onError: function() {
						require('app').UI.showError(t('mail', 'Error while loading messages.'));
						// Set the old folder as being active
						require('app').UI.setFolderActive(require('app').State.currentAccountId,
							require('app').State.currentFolderId);
					},
					onComplete: function() {
						// Remove loading feedback again
						$('#load-more-mail-messages')
							.removeClass('icon-loading-small')
							.val(t('mail', 'Load more …'))
							.prop('disabled', false);
						$('#load-new-mail-messages')
							.removeClass('icon-loading-small')
							.val(t('mail', 'Check messages …'))
							.prop('disabled', false);
					}
				});
		}
	});
});
