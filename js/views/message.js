/* global Backbone, Marionette, Mail, models */

var views = views || {};

$('.action.delete').tipsy({gravity:'e', live:true});

views.DetailedMessage = Backbone.Marionette.ItemView.extend({
	template: "#mail-message-template"
});

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
		_.each(this.$el.find('.avatar'), function(a) {
			$(a).height('32px');
			$(a).imageplaceholder(displayName, displayName);
		});
	},

	toggleMessageStar: function(event) {
		event.stopPropagation();

		var messageId = this.model.id;
		var starred = this.model.get('flags').get('flagged');
		var thisModel = this.model;

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

		$.ajax(
			OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/toggleStar',
			{
				accountId: Mail.State.currentAccountId,
				folderId: Mail.State.currentFolderId,
				messageId: messageId
			}), {
				data: {
					starred: starred
				},
				type:'POST',
				success: function () {
					thisModel.get('flags').set('flagged', !starred);
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Message could not be starred. Please try again.'));
					thisModel.get('flags').set('flagged', starred);
				}
			});
	},

	openMessage: function(event) {
		event.stopPropagation();
		Mail.UI.openMessage(this.model.id, true);
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
			var nextMessage = thisModelCollection.at(index+1);
			if (!nextMessage) {
				nextMessage = thisModelCollection.at(index-1);
			}
			thisModelCollection.remove(thisModel);
			if (Mail.State.currentMessageId === thisModel.id) {
				if (nextMessage) {
					Mail.UI.openMessage(nextMessage.id);
				}
			}
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
					// delete local storage draft
					var storage = $.localStorage;
					var draftId = 'draft' +
						'.' + Mail.State.currentAccountId.toString() +
						'.' + Mail.State.currentFolderId.toString() +
						'.' + thisModel.id;
					if (storage.isSet(draftId)) {
						storage.remove(draftId);
					}
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
			message
				.get('flags')
				.set(flag, val);
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
			.val(t('mail', 'Checking mail …'))
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
//		$('#load-new-mail-messages').show();
//		$('#load-more-mail-messages').hide();
		$('#load-more-mail-messages')
			.addClass('icon-loading-small')
			.val(t('mail', 'Loading …'))
			.prop('disabled', true);

		var url = OC.generateUrl(
			'apps/mail/accounts/{accountId}/folders/{folderId}/messages?from={from}&to={to}',
			{
				'accountId': Mail.State.currentAccountId,
				'folderId':Mail.State.currentFolderId,
				'from': from,
				'to': from + 20
			});
		if (this.filterCriteria) {
			url = OC.generateUrl(
				'apps/mail/accounts/{accountId}/folders/{folderId}/messages?filter={query}&from={from}&to={to}',
				{
					'accountId': Mail.State.currentAccountId,
					'folderId':Mail.State.currentFolderId,
					'query': this.filterCriteria.text,
					'from': from,
					'to': from + 20
				});
		}
		var self = this;
		$.ajax(url, {
				data: {},
				type:'GET',
				success: function (jsondata) {
					if (reload){
						self.collection.reset();
					}
					// Add messages
					self.collection.add(jsondata);

					$('#app-content').removeClass('icon-loading');

					Mail.UI.setMessageActive(Mail.State.currentMessageId);
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading messages.'));
					// Set the old folder as being active
					Mail.UI.setFolderActive(Mail.State.currentAccountId, Mail.State.currentFolderId);
				},
				complete: function() {
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
