/* global Backbone, Handlebars, models, OC, Mail */

var views = views || {};

views.SendMail = Backbone.View.extend({

	// The collection will be kept here
	attachments: null,

	sentCallback: null,

	aliases: null,
	currentAccountId: null,
	data: null,
	draftIntervalIMAP: 1500,
	draftIntervalLocal: 100,
	draftTimerIMAP: null,
	draftTimerLocal: null,
	draftUID: null,

	events: {
		"click #new-message-send" : "sendMail",
		"click #new-message-draft" : "saveDraft",
		"keypress #new-message-body" : "handleKeyPress",
		"keyup #new-message-body": "handleKeyUp",
		"keyup #to": "handleKeyUp",
		"keyup #cc": "handleKeyUp",
		"keyup #bcc": "handleKeyUp",
		"keyup #subject": "handleKeyUp",
		"click .mail_account" : "changeAlias"
	},

	initialize: function(options) {
		this.attachments = new models.Attachments();
		this.aliases = options.aliases;
		if (options.data) {
			this.data = options.data;
			this.draftUID = options.data.id;
		}
		this.el = options.el;
		this.currentAccountId = this.aliases[0].accountId;
	},

	changeAlias: function(event) {
		this.currentAccountId = parseInt($(event.target).val(), 10);
	},

	handleKeyPress: function(event) {
		var key = event.keyCode || event.which;
		var sendBtnState = $('#new-message-send').attr('disabled');

		// check for ctrl+enter
		if (key === 13 && event.ctrlKey) {
			if (sendBtnState === undefined) {
				this.sendMail();
			}
		}
		return true;
	},

	handleKeyUp: function() {
		clearTimeout(this.draftTimerIMAP);
		clearTimeout(this.draftIntervalLocal);
		var self = this;
		this.draftTimerIMAP = setTimeout(function() {
			self.saveDraft();
		}, this.draftIntervalIMAP);
		this.draftTimerLocal = setTimeout(function() {
			self.saveDraftLocally();
		}, this.draftIntervalLocal);
	},

	getMessage: function() {
		var message = {};
		var newMessageBody = $('#new-message-body');
		var to = $('#to');
		var cc = $('#cc');
		var bcc = $('#bcc');
		var subject = $('#subject');

		message.body = newMessageBody.val();
		message.to = to.val();
		message.cc = cc.val();
		message.bcc = bcc.val();
		message.subject = subject.val();
		message.attachments = this.attachments.toJSON();

		return message;
	},

	sendMail: function() {
		clearTimeout(this.draftTimerIMAP);
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

		var message = this.getMessage();
		var self = this;
		// send the mail
		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: this.currentAccountId}),
			type: 'POST',
			data:{
				'to': message.to,
				'cc': message.cc,
				'bcc': message.bcc,
				'subject': message.subject,
				'body': message.body,
				'attachments': message.attachments,
				'draftUID' : this.draftUID
			},
			success:function () {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				// close composer
				if (self.sentCallback !== null) {
					self.sentCallback();
				} else {
					$('#new-message').slideUp();
				}
				$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				newMessageBody.val('');
				newMessageBody.trigger('autosize.resize');
				self.attachments.reset();
				if (self.draftUID !== null) {
					// the sent message was a draft
					Mail.State.messageView.collection.remove({id: self.draftUID});
					self.draftUID = null;
				}
			},
			error: function (jqXHR) {
				OC.Notification.showTemporary(jqXHR.responseJSON.message);
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

	saveDraftLocally: function() {
		var storage = $.localStorage;
		storage.set("draft", "default", this.getMessage());
	},

	saveDraft: function() {
		clearTimeout(this.draftTimerIMAP);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		var message = this.getMessage();
		var self = this;
		// send the mail
		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/draft', {accountId: this.currentAccountId}),
			beforeSend:function () {
				OC.msg.startAction('#new-message-msg', "");
			},
			type: 'POST',
			data: {
				'to': message.to,
				'cc': message.cc,
				'bcc': message.bcc,
				'subject': message.subject,
				'body': message.body,
				'uid': self.draftUID
			},
			success: function (data) {
				if (self.draftUID !== null) {
					// update UID in message list
					var message = Mail.State.messageView.collection.findWhere({id: self.draftUID});
					if (message) {
						message.set({id: data.uid});
						Mail.State.messageView.collection.set([message], {remove: false});
					}
				}
				self.draftUID = data.uid;
				OC.msg.finishedAction('#new-message-msg', {
					status: 'success',
					data: {
						message: t('mail', 'Draft saved!')
					}
				});
			},
			error: function (jqXHR) {
				OC.msg.finishedAction('#new-message-msg', {
					status: 'error',
					data: {
						message: jqXHR.responseJSON.message
					}
				});
			}
		});
		return false;
	},

	render: function() {
		var source   = $("#new-message-template").html();
		var template = Handlebars.compile(source);
		var data = {
			aliases: this.aliases
		};

		// draft data
		if (this.data) {
			data.to = this.data.toEmail;
			data.subject = this.data.subject;
			data.message = this.data.body;
		}

		var html = template(data);

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
