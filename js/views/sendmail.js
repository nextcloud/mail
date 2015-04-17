/* global Backbone, Handlebars, models, escapeHTML */

var views = views || {};

views.SendMail = Backbone.View.extend({

	// The collection will be kept here
	attachments: null,

	sentCallback: null,

	aliases: null,
	currentAccountId: null,

	events: {
		"click #new-message-send" : "sendMail",
		"keypress #new-message-body" : "handleKeyPress",
		"click .mail_account" : "changeAlias"
	},

	initialize: function(options) {
		this.attachments = new models.Attachments();
		this.aliases = options.aliases;
		this.el = options.el;
		this.currentAccountId = this.aliases[0].accountId;
	},

	changeAlias: function(event) {
		this.currentAccountId = parseInt($(event.target).val(), 10);
	},

	handleKeyPress: function(event) {
		// check for ctrl+enter
		if (event.keyCode === 13 && event.ctrlKey) {
			var sendBtnState = $('#new-message-send').attr('disabled');
			if (sendBtnState === undefined) {
				this.sendMail();
			}
		}
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
		var to = $('#to');
		var cc = $('#cc');
		var bcc = $('#bcc');
		var subject = $('#subject');
		$('.mail_account').prop('disabled', true);
		to.prop('disabled', true);
		cc.prop('disabled', true);
		bcc.prop('disabled', true);
		subject.prop('disabled', true);
		$('.new-message-attachments-action').css('display', 'none');
		$('#mail_new_attachment').prop('disabled', true);
		newMessageBody.prop('disabled', true);
		newMessageSend.prop('disabled', true);
		newMessageSend.val(t('mail', 'Sending …'));

		var self = this;
		// send the mail
		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: this.currentAccountId}),
			beforeSend:function () {
				OC.msg.startAction('#new-message-msg', "");
			},
			type: 'POST',
			data:{
				'to': to.val(),
				'cc': cc.val(),
				'bcc': bcc.val(),
				'subject': subject.val(),
				'body':newMessageBody.val(),
				'attachments': self.attachments.toJSON()
			},
			success:function () {
				OC.msg.finishedAction('#new-message-msg', {
					status: 'success',
					data: {
						message: t('mail', 'Message sent!')
					}
				});

				// close composer
				if (self.sentCallback !== null) {
					self.sentCallback();
				} else {
					$('#new-message').slideUp();
				}
				$('#mail_new_message').prop('disabled', false);
				$('#to').val('');
				$('#cc').val('');
				$('#bcc').val('');
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
				$('.mail_account').prop('disabled', false);
				$('#to').prop('disabled', false);
				$('#cc').prop('disabled', false);
				$('#bcc').prop('disabled', false);
				$('#subject').prop('disabled', false);
				$('.new-message-attachments-action').css('display', 'inline-block');
				$('#mail_new_attachment').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			}
		});

		return false;
	},

	render: function() {
		var source   = $("#new-message-template").html();
		var template = Handlebars.compile(source);
		var html = template({aliases: this.aliases});

		this.$el.html(html);

		var view = new views.Attachments({
			el: $('#new-message-attachments'),
			collection: this.attachments
		});

		// And render it
		view.render();

		$('textarea').autosize({append:'"\n\n"'});

		return this;
	}
});
