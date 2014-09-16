/* global Backbone, Handlebars, Mail, models */

var views = views || {};

views.Message = Backbone.Marionette.ItemView.extend({

	template: "#mail-messages-template",

	onRender: function () {
		// Get rid of that pesky wrapping-div.
		// Assumes 1 child element present in template.
		this.$el = this.$el.children();
		// Unwrap the element to prevent infinitely
		// nesting elements during re-render.
		this.$el.unwrap();
		this.setElement(this.$el);
	}

});

views.Messages = Backbone.Marionette.CompositeView.extend({

	// The collection will be kept here
	collection: null,

	childView: views.Message,

	childViewContainer: '#mail-message-list',

	events: {
		"click #load-new-mail-messages" : "loadNew",
		"click #load-more-mail-messages" : "loadMore"
	},

	template: "#message-list-template",

	initialize: function() {
		this.collection = new models.MessageList();
	},

	loadNew: function() {
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
		$.ajax(
			OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages?from={from}&to={to}',
				{
				'accountId': Mail.State.currentAccountId,
				'folderId':encodeURIComponent(Mail.State.currentFolderId),
				'from': from,
				'to': from + 20
			}), {
				data: {},
				type:'GET',
				success: function (jsondata) {
					if (reload){
						self.collection.reset();
					}
					// Add messages
					Mail.State.messageView.collection.add(jsondata);

					_.each($('.avatar'), function(a) {
							$(a).imageplaceholder($(a).data('user'), $(a).data('user'));
						}
					);
					$('#app-content').removeClass('icon-loading');

					Mail.State.currentMessageId = null;
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
						.val(t('mail', 'Check mail'))
						.prop('disabled', false);
				}
			});
	}

});
