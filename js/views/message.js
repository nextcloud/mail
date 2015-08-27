/* global Backbone, Marionette, Mail, models, OC */

var views = views || {};

$('.action.delete').tipsy({gravity:'e', live:true});
$('.tipsy-mailto').tipsy({gravity:'n', live:true});

views.Message = Backbone.Marionette.ItemView.extend({

	template: "#mail-messages-template",

	ui:{
		iconDelete : '.action.delete',
		star : '.star'
	},

	events: {
		"click .action.delete" : "deleteMessage",
		"click .mail-message-header" : "openMessage",
		"click .star" : "toggleMessageStar"
	},

	onRender: function () {
		// Get rid of that pesky wrapping-div.
		// Assumes 1 child element present in template.
		this.$el = this.$el.children();
		// Unwrap the element to prevent infinitely
		// nesting elements during re-render.
		this.$el.unwrap();
		this.setElement(this.$el);

		var displayName = this.model.get('from');
		// Don't show any placeholder if 'from' isn't set
		if (displayName) {
			_.each(this.$el.find('.avatar'), function (a) {
				$(a).height('32px');
				$(a).imageplaceholder(displayName, displayName);
			});
		}
	},

	toggleMessageStar: function(event) {
		event.stopPropagation();

		var starred = this.model.get('flags').get('flagged');

		// directly change star state in the interface for quick feedback
		if(starred) {
			this.ui.star
				.removeClass('icon-starred')
				.addClass('icon-star');
		} else {
			this.ui.star
				.removeClass('icon-star')
				.addClass('icon-starred');
		}
		this.model.flagMessage(
			'flagged',
			!starred
		);
	},

	openMessage: function(event) {
		event.stopPropagation();
		$('#mail-message').removeClass('hidden-mobile');
		Mail.UI.loadMessage(this.model.id, {
			force: true
		});
	},

	deleteMessage: function(event) {
		event.stopPropagation();
		var thisModel = this.model;
		this.ui.iconDelete.removeClass('icon-delete').addClass('icon-loading');
		$('.tipsy').remove();

		thisModel.get('flags').set('unseen', false);

		this.$el.addClass('transparency').slideUp(function() {
			$('.tipsy').remove();
			var thisModelCollection = thisModel.collection;
			var index = thisModelCollection.indexOf(thisModel);
			var nextMessage = thisModelCollection.at(index-1);
			if (!nextMessage) {
				nextMessage = thisModelCollection.at(index+1);
			}
			thisModelCollection.remove(thisModel);
			if (Mail.State.currentMessageId === thisModel.id) {
				if (nextMessage) {
					Mail.UI.loadMessage(nextMessage.id);
				}
			}
			// manually trigger mouseover event for current mouse position
			// in order to create a tipsy for the next message if needed
			$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
		});

		// really delete the message
		$.ajax(
			OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
				{
				accountId: Mail.State.currentAccountId,
				folderId: Mail.State.currentFolderId,
				messageId: thisModel.id
			}), {
				data: {},
				type:'DELETE',
				success: function () {
					Mail.Cache.removeMessage(Mail.State.currentAccountId, Mail.State.currentFolderId, thisModel.id);
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while deleting message.'));
				}
			});
	}


});

views.NoSearchResultMessageListView = Marionette.ItemView.extend({
	initialize: function(options) {
		this.model.set('searchTerm', options.filterCriteria.text || "");
	},

	template: "#no-search-results-message-list-template",

	onRender: function() {
		$('#load-more-mail-messages').hide();
	}
});

views.Messages = Backbone.Marionette.CompositeView.extend({

	collection: null,

	childView: views.Message,

	childViewContainer: '#mail-message-list',

	currentMessageId: null,

	events: {
		"click #load-new-mail-messages" : "loadNew",
		"click #load-more-mail-messages" : "loadMore"
	},

	filterCriteria: null,

	template: "#message-list-template",

	initialize: function() {
		this.collection = new models.MessageList();
		this.collection.on('change:flags', this.changeFlags, this);
	},

	getEmptyView: function() {
		if (this.filterCriteria) {
			return views.NoSearchResultMessageListView;
		}
		return views.template;
	},

	emptyViewOptions: function () {
		return { filterCriteria: this.filterCriteria };
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
		if(this.currentMessageId !== null) {
			message = this.collection.get(this.currentMessageId);
			if (message) {
				message.set('active', false);
			}
		}

		this.currentMessageId = messageId;

		if(messageId !== null) {
			message = this.collection.get(this.currentMessageId);
			if (message) {
				message.set('active', true);
			}
		}

	},

	loadNew: function() {
		if (!Mail.State.currentAccountId) {
			return;
		}
		if (!Mail.State.currentFolderId) {
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
		if (reload){
			from = 0;
		}
		// Add loading feedback
		$('#load-more-mail-messages')
			.addClass('icon-loading-small')
			.val(t('mail', 'Loading …'))
			.prop('disabled', true);

		var self = this;
		Mail.Communication.fetchMessageList(
			Mail.State.currentAccountId,
			Mail.State.currentFolderId,
			{
				from: from,
				to: from + 20,
				filter: this.filterCriteria ? this.filterCriteria.text : null,
				force: true,
				// Replace cached message list on reload
				replace: reload,
				onSuccess: function (jsondata) {
					if (reload){
						self.collection.reset();
					}
					// Add messages
					self.collection.add(jsondata);

					$('#app-content').removeClass('icon-loading');

					Mail.UI.setMessageActive(Mail.State.currentMessageId);
				},
				onError: function() {
					Mail.UI.showError(t('mail', 'Error while loading messages.'));
					// Set the old folder as being active
					Mail.UI.setFolderActive(Mail.State.currentAccountId, Mail.State.currentFolderId);
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
