/* global Backbone, Handlebars, Mail */

var views = {};

views.Attachment = Backbone.View.extend({
	// Each attachment will be shown as a li row
	tagName: 'li',

	initialize: function() {
		// Ensure our methods keep the `this` reference to the view itself
		_.bindAll(this, 'render');

		// If the model changes we need to re-render
		this.model.bind('change', this.render);
	},

	render: function() {
		// Clear existing row data if needed
		var tpl = Handlebars.compile($("#mail-attachment-template").html());
		$(this.el).html(tpl(this.model.toJSON()));
		return this;
	}
});

views.Attachments = Backbone.View.extend({

	// The collection will be kept here
	collection: null,

	events: {
		"click #mail_new_attachment" : "addAttachment",
		"click #new-message-send" : "sendMail"
	},

	initialize: function(options) {
		this.collection = options.collection;

		// Ensure our methods keep the `this` reference to the view itself
		_.bindAll(this, 'render');

		// Bind collection changes to re-rendering
		this.collection.bind('reset', this.render);
		this.collection.bind('add', this.render);
		this.collection.bind('remove', this.render);
	},

	addAttachment: function() {
		var self = this;
		OC.dialogs.filepicker(
			t('mail', 'Choose a folder store the attachment'),
			function (path) {
				self.collection.add([
					{
						fileName: path
					}
				]);
			});
	},

	sendMail: function() {
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// loading feedback: show spinner and disable elements
		var newMessageBody = $('#new-message-body');
		var newMessageSend = $('#new-message-send');
		newMessageBody.addClass('icon-loading');
		$('#to').prop('disabled', true);
		$('#cc').prop('disabled', true);
		$('#bcc').prop('disabled', true);
		$('#subject').prop('disabled', true);
		newMessageBody.prop('disabled', true);
		newMessageSend.prop('disabled', true);
		newMessageSend.val(t('mail', 'Sending …'));

		var self = this;
		// send the mail
		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: Mail.State.currentAccountId}),
			beforeSend:function () {
//				$('#wait').show();
			},
			type: 'POST',
			complete:function () {
//				$('#wait').hide();
			},
			data:{
				'to':$('#to').val(),
				'cc':$('#cc').val(),
				'bcc':$('#bcc').val(),
				'subject':$('#subject').val(),
				'body':newMessageBody.val(),
				'attachments': self.collection.toJSON()
			},
			success:function () {
				// close composer
				$('#new-message-fields').slideUp();
				$('#mail_new_message').fadeIn();
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				$('#to').prop('disabled', false);
				$('#cc').prop('disabled', false);
				$('#bcc').prop('disabled', false);
				$('#subject').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			}
		});

		return false;
	},

	render: function() {
		var element = this.$el.find('ul');
		// Clear potential old entries first
		element.empty();

		// Go through the collection items
		this.collection.forEach(function(item) {

			// Instantiate a PeopleItem view for each
			var itemView = new views.Attachment({
				model: item
			});

			// Render the PeopleView, and append its element
			// to the table
			element.append(itemView.render().el);
		});

		return this;
	}
});
