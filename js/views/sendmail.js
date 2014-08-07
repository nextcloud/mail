/* global Backbone, Mail, models */

var views = views || {};

views.SendMail = Backbone.View.extend({

	// The collection will be kept here
	attachments: null,

	events: {
		"click #new-message-send" : "sendMail"
	},

	initialize: function(options) {
		this.attachments = new models.Attachments();
		this.collection = options.collection;

		var view = new views.Attachments({
			el: $('#new-message-attachments'),
			collection: this.attachments
		});

		// And render it
		view.render();
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
		$('.new-message-attachments-action').css('display', 'none');
		$('#mail_new_attachment').prop('disabled', true);
		newMessageBody.prop('disabled', true);
		newMessageSend.prop('disabled', true);
		newMessageSend.val(t('mail', 'Sending …'));

		var self = this;
		// send the mail
		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: Mail.State.currentAccountId}),
			beforeSend:function () {
				OC.msg.startAction('#new-message-msg', {});
			},
			type: 'POST',
			data:{
				'to':$('#to').val(),
				'cc':$('#cc').val(),
				'bcc':$('#bcc').val(),
				'subject':$('#subject').val(),
				'body':newMessageBody.val(),
				'attachments': self.attachments.toJSON()
			},
			success:function () {
				// close composer
				$('#new-message').slideUp();
				$('#mail_new_message').prop('disabled', false);
				$('#to').val('');
				$('#subject').val('');
				$('#new-message-body').val('');
				self.attachments.reset();
			},
			error: function (jqXHR) {
				OC.msg.finishedAction('#new-message-msg', {
					status: 'error',
					data: {
						message: jqXHR.responseJSON.message
					}
				});
			},
			complete: function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				$('#to').prop('disabled', false);
				$('#cc').prop('disabled', false);
				$('#bcc').prop('disabled', false);
				$('#subject').prop('disabled', false);
				$('.new-message-attachments-action').css('display', 'inline-block');
				$('#mail_new_attachment').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			}
		});

		return false;
	},

	render: function() {
		return this;
	}
});
