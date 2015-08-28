/* global Backbone, Handlebars, models, OC, Mail, _ */

var views = views || {};

views.Composer = Backbone.View.extend({

	type: 'new',

	attachments: null,

	submitCallback: null,
	sentCallback: null,
	draftCallback: null,

	aliases: null,
	accountId: null,
	folderId: null,
	messageId: null,

	draftInterval: 1500,
	draftTimer: null,
	draftUID: null,
	hasData: false,
	autosized: false,

	events: {
		"click .submit-message":  "submitMessage",
		"keypress .message-body": "handleKeyPress",
		"input  .to":             "onInputChanged",
		"paste  .to":             "onInputChanged",
		"keyup  .to":             "onInputChanged",
		"input  .cc":             "onInputChanged",
		"paste  .cc":             "onInputChanged",
		"keyup  .cc":             "onInputChanged",
		"input  .bcc":            "onInputChanged",
		"paste  .bcc":            "onInputChanged",
		"keyup  .bcc":            "onInputChanged",
		"input  .subject":        "onInputChanged",
		"paste  .subject":        "onInputChanged",
		"keyup  .subject":        "onInputChanged",
		"input  .message-body":   "onInputChanged",
		"paste  .message-body":   "onInputChanged",
		"keyup  .message-body":   "onInputChanged",

		// CC/BCC toggle
		"click .composer-cc-bcc-toggle": "ccBccToggle"
	},

	initialize: function(options) {
		var defaultOptions = {
			type: 'new',
			onSubmit: function() {},
			onDraft: function() {},
			onSent: function() {},
			accountId: null,
			folderId: null,
			messageId: null
		};
		_.defaults(options, defaultOptions);

		/**
		 * Composer type (new, reply)
		 */
		this.type = options.type;

		/**
		 * Containing element
		 */
		this.el = options.el;

		/**
		 * Callback functions
		 */
		this.submitCallback = options.onSubmit;
		this.draftCallback = options.onDraft;
		this.sentCallback = options.onSent;

		/**
		 * Attachments sub-view
		 */
		this.attachments = new models.Attachments();

		if (!this.isReply()) {
			this.aliases = _.filter(options.aliases, function(item) {
				return item.accountId !== -1;
			});
			this.accountId = options.accountId || this.aliases[0].accountId;
		} else {
			this.accountId = options.accountId;
			this.folderId = options.folderId;
			this.messageId = options.messageId;
		}
	},

	render: function(options) {
		options = options || {};
		var defaultOptions = {
			data: {
				to: '',
				cc: '',
				subject: '',
				body: ''
			}
		};
		_.defaults(options, defaultOptions);

		var source   = $("#mail-composer").html();
		var template = Handlebars.compile(source);

		this.attachments.reset();

		// Render handlebars template
		var html = template({
			aliases: this.aliases,
			isReply: this.isReply(),
			to: options.data.to,
			cc: options.data.cc,
			subject: options.data.subject,
			message: options.data.body,
			submitButtonTitle: this.isReply() ? t('mail', 'Reply') : t('mail' , 'Send'),

			// Reply data
			replyToList: options.data.replyToList,
			replyCc: options.data.replyCc,
			replyCcList: options.data.replyCcList
		});
		
		$('.tipsy-mailto').tipsy({gravity:'n', live:true});

		this.$el.html(html);

		var view = new views.Attachments({
			el: $('.new-message-attachments'),
			collection: this.attachments
		});

		// And render it
		view.render();

		if (this.isReply()) {
			// Expand reply message body on click
			var _this = this;
			this.$('.message-body').click(function() {
				_this.setAutoSize(true);
			});
		} else {
			this.setAutoSize(true);
		}

		return this;
	},

	setAutoSize: function(state) {
		if (state === true) {
			if (!this.autosized) {
				this.$('textarea').autosize({append:'"\n\n"'});
				this.autosized = true;
			}
			this.$('.message-body').trigger('autosize.resize');
		} else {
			this.$('.message-body').trigger('autosize.destroy');

			// dirty workaround to set reply message body to the default size
			this.$('.message-body').css('height', '');
			this.autosized = false;
		}
	},

	isReply: function() {
		return this.type === 'reply';
	},

	onInputChanged: function() {
		// Submit button state
		var to = this.$('.to').val();
		var subject = this.$('.subject').val();
		var body = this.$('.message-body').val();
		if (to !== '' || subject !== '' || body !== '') {
			this.$('.submit-message').removeAttr('disabled');
		} else {
			this.$('.submit-message').attr('disabled', true);
		}

		// Save draft
		this.hasData = true;
		clearTimeout(this.draftTimer);
		var _this = this;
		this.draftTimer = setTimeout(function() {
			_this.saveDraft();
		}, this.draftInterval);
	},

	changeAlias: function(event) {
		this.accountId = parseInt(this.$(event.target).val(), 10);
	},

	handleKeyPress: function(event) {
		// Define which objects to check for the event properties.
		// (Window object provides fallback for IE8 and lower.)
		event = event || window.event;
		var key = event.keyCode || event.which;
		// If enter and control keys are pressed:
		// (Key 13 and 10 set for compatibility across different operating systems.)
		if ((key === 13 || key === 10) && event.ctrlKey) {
			// If the new message is completely filled, and ready to be sent:
			// Send the new message.
			var sendBtnState = this.$('.submit-message').attr('disabled');
			if (sendBtnState === undefined) {
				this.submitMessage();
			}
		}
		return true;
	},

	ccBccToggle: function() {
		this.$('.composer-cc-bcc').slideToggle();
		this.$('.composer-cc-bcc .cc').focus();
		this.$('.composer-cc-bcc-toggle').fadeOut();
	},

	getMessage: function() {
		var message = {};
		var newMessageBody = this.$('.message-body');
		var to = this.$('.to');
		var cc = this.$('.cc');
		var bcc = this.$('.bcc');
		var subject = this.$('.subject');

		message.body = newMessageBody.val();
		message.to = to.val();
		message.cc = cc.val();
		message.bcc = bcc.val();
		message.subject = subject.val();
		message.attachments = this.attachments.toJSON();

		return message;
	},

	submitMessage: function() {
		clearTimeout(this.draftTimer);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// loading feedback: show spinner and disable elements
		var newMessageBody = this.$('.message-body');
		var newMessageSend = this.$('.submit-message');
		newMessageBody.addClass('icon-loading');
		var to = this.$('.to');
		var cc = this.$('.cc');
		var bcc = this.$('.bcc');
		var subject = this.$('.subject');
		this.$('.mail-account').prop('disabled', true);
		to.prop('disabled', true);
		cc.prop('disabled', true);
		bcc.prop('disabled', true);
		subject.prop('disabled', true);
		this.$('.new-message-attachments-action').css('display', 'none');
		this.$('#mail_new_attachment').prop('disabled', true);
		newMessageBody.prop('disabled', true);
		newMessageSend.prop('disabled', true);
		newMessageSend.val(t('mail', 'Sending …'));

		// if available get account from drop-down list
		if (this.$('.mail-account').length > 0) {
			this.accountId = this.$('.mail-account').find(":selected").val();
		}

		// send the mail
		var _this = this;
		var options = {
			draftUID: this.draftUID,
			success: function () {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				// close composer
				if (_this.sentCallback !== null) {
					_this.sentCallback();
				} else {
					_this.$('.message-composer').slideUp();
				}
				_this.$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				newMessageBody.val('');
				newMessageBody.trigger('autosize.resize');
				_this.attachments.reset();
				if (_this.draftUID !== null) {
					// the sent message was a draft
					if (!_.isUndefined(Mail.UI.messageView)) {
						Mail.UI.messageView.collection.remove({id: _this.draftUID});
					}
					_this.draftUID = null;
				}
			},
			error: function (jqXHR) {
				var error = '';
				if (jqXHR.status === 500) {
					error = t('mail', 'Server error');
				} else {
					var resp = JSON.parse(jqXHR.responseText);
					error = resp.message;
				}
				newMessageSend.prop('disabled', false);
				OC.Notification.showTemporary(error);
			},
			complete: function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				_this.$('.mail-account').prop('disabled', false);
				to.prop('disabled', false);
				cc.prop('disabled', false);
				bcc.prop('disabled', false);
				subject.prop('disabled', false);
				_this.$('.new-message-attachments-action').css('display', 'inline-block');
				_this.$('#mail_new_attachment').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			}
		};

		if (this.isReply()) {
			options.messageId = this.messageId;
			options.folderId = this.folderId;
		}

		this.submitCallback(this.accountId, this.getMessage(), options);
		return false;
	},

	saveDraft: function(onSuccess) {
		clearTimeout(this.draftTimer);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// send the mail
		var _this = this;
		this.draftCallback(this.accountId, this.getMessage(), {
			accountId: this.accountId,
			folderId: this.folderId,
			messageId: this.messageId,
			draftUID: this.draftUID,
			success: function (data) {
				if (_.isFunction(onSuccess)) {
					onSuccess();
				}
				_this.draftUID = data.uid;
			},
			error: function () {
				// TODO: show error
			}
		});
		return false;
	},

	setReplyBody: function(from, date, text) {
		var minutes = date.getMinutes();

		this.$('.message-body').first().text(
			'\n\n\n' +
			from + ' – ' +
			$.datepicker.formatDate('D, d. MM yy ', date) +
			date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
			text.replace(/\n/g, '\n> ')
		);

		this.setAutoSize(false);
		// Expand reply message body on click
		var _this = this;
		this.$('.message-body').click(function() {
			_this.setAutoSize(true);
		});
	}
});
