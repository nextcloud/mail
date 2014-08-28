/* global Backbone, Handlebars, Mail, models */

var views = views || {};

views.Messages = Backbone.View.extend({

	// The collection will be kept here
	collection: null,

	template: null,

	events: {
		"click #load-new-mail-messages" : "loadNew",
		"click #load-more-mail-messages" : "loadMore"
	},

	initialize: function() {
		this.collection = new models.MessageList();

		this.template = Handlebars.compile($("#mail-messages-template").html());

		// Ensure our methods keep the `this` reference to the view itself
		_.bindAll(this, 'render');

		// Bind collection changes to re-rendering
		this.collection.bind('reset', this.render);
		this.collection.bind('add', this.render);
		this.collection.bind('remove', this.render);
	},

	loadNew: function() {
		// Add loading feedback
		$('#load-new-mail-messages')
			.addClass('icon-loading-small')
			.val(t('mail', 'Checking mail …'))
			.prop('disabled', true);

		this.loadMore(true);
	},

	loadMore: function(reload) {
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

		self = this;
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
					Mail.UI.addMessages(jsondata);
					$('#app-content').removeClass('icon-loading');

					Mail.State.currentMessageId = null;
				},
				error: function() {

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
	},

	render: function() {
		// Clear potential old entries first
		var element = this.$el.find('#mail-message-list');
		element.empty();

		var html = this.template(this.collection.toJSON());
		element.append(html);

		return this;
	}
});
