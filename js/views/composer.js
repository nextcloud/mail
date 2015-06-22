/* global Backbone, Handlebars, models, OC, Mail, _ */

var views = views || {};

views.Composer = Backbone.View.extend((function() {

	var $ = this.$;

	var attachments = null;

	var submitCallback = null;
	var sentCallback = null;
	var draftCallback = null;

	var aliases = null;
	var accountId = null;

	var draftInterval = 1500;
	var draftTimer = null;
	var draftUID = null;
	var hasData = false;

	var events = {
		"click .submit-message":  "submitMessage",
		"keypress .message-body": "handleKeyPress",
		"keyup .message-body":    "handleKeyUp",
		"keyup .to":              "handleKeyUp",
		"keyup .cc":              "handleKeyUp",
		"keyup .bcc":             "handleKeyUp",
		"keyup .subject":         "handleKeyUp",
		"click .mail_account":    "changeAlias"
	};

	function initialize(options) {
		var defaultOptions = {
			onSubmit: function() {},
			onDraft: function() {},
			onSent: function() {},
			accountId: null,
			folderId: null,
			messageId: null
		};
		_.defaults(options, defaultOptions);

		/**
		 * Containing element
		 */
		this.el = options.el;

		/**
		 * Callback functions
		 */
		submitCallback = options.onSubmit;
		draftCallback = options.onDraft;
		sentCallback = options.onSent;

		/**
		 * Attachments sub-view
		 */
		attachments = new models.Attachments();

		aliases = _.filter(options.aliases, function(item) {
			return item.accountId !== -1;
		});
		accountId = aliases[0].accountId;
	}

	function render(options) {
		var defaultOptions = {
			data: {
				to: '',
				subject: '',
				body: ''
			}
		};
		_.defaults(options, defaultOptions);

		var source   = jQuery("#mail-composer").html();
		var template = Handlebars.compile(source);

		attachments.reset();

		// Render handlebars template
		var html = template({
			aliases: aliases,

			/**
			 * Draft data
			 */
			to: options.data.to,
			subject: options.data.subject,
			message: options.data.body
		});

		this.$el.html(html);

		var view = new views.Attachments({
			el: jQuery('#new-message-attachments'),
			collection: attachments
		});

		// And render it
		view.render();

		// CC/BCC toggle
		$('.composer-cc-bcc-toggle').click(function() {
			$('.composer-cc-bcc').slideToggle();
			$('.composer-cc-bcc .cc').focus();
			$('.composer-cc-bcc-toggle').fadeOut();
		});

		// Submit button state toggle
		$('.to').on('change input paste keyup', toggleSubmitButton);
		$('.subject').on('change input paste keyup', toggleSubmitButton);
		$('.message-body').on('change input paste keyup', toggleSubmitButton);

		$('textarea').autosize({append:'"\n\n"'});

		return this;
	}

	function toggleSubmitButton() {
		var to = $('.to').val();
		var subject = $('.subject').val();
		var body = $('.message-body').val();
		if (to !== '' || subject !== '' || body !== '') {
			$('.submit-message').removeAttr('disabled');
		} else {
			$('.submit-message').attr('disabled', true);
		}
	}

	function changeAlias(event) {
		accountId = parseInt($(event.target).val(), 10);
	}

	function handleKeyPress(event) {
		// Define which objects to check for the event properties.
		// (Window object provides fallback for IE8 and lower.)
		event = event || window.event;
		var key = event.keyCode || event.which;
		// If enter and control keys are pressed:
		// (Key 13 and 10 set for compatibility across different operating systems.)
		if ((key === 13 || key === 10) && event.ctrlKey) {
			// If the new message is completely filled, and ready to be sent:
			// Send the new message.
			var sendBtnState = $('.submit-message').attr('disabled');
			if (sendBtnState === undefined) {
				submitMessage();
			}
		}
		return true;
	}

	function handleKeyUp() {
		hasData = true;
		clearTimeout(draftTimer);
		draftTimer = setTimeout(function() {
			saveDraft();
		}, draftInterval);
	}

	function getMessage() {
		var message = {};
		var newMessageBody = $('.message-body');
		var to = $('.to');
		var cc = $('.cc');
		var bcc = $('.bcc');
		var subject = $('.subject');

		message.body = newMessageBody.val();
		message.to = to.val();
		message.cc = cc.val();
		message.bcc = bcc.val();
		message.subject = subject.val();
		message.attachments = attachments.toJSON();

		return message;
	}

	function submitMessage() {
		clearTimeout(draftTimer);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// loading feedback: show spinner and disable elements
		var newMessageBody = $('.message-body');
		var newMessageSend = $('.submit-message');
		newMessageBody.addClass('icon-loading');
		var to = $('.to');
		var cc = $('.cc');
		var bcc = $('.bcc');
		var subject = $('.subject');
		$('.mail-account').prop('disabled', true);
		to.prop('disabled', true);
		cc.prop('disabled', true);
		bcc.prop('disabled', true);
		subject.prop('disabled', true);
		$('.new-message-attachments-action').css('display', 'none');
		$('#mail_new_attachment').prop('disabled', true);
		newMessageBody.prop('disabled', true);
		newMessageSend.prop('disabled', true);
		newMessageSend.val(t('mail', 'Sending …'));

		// send the mail
		submitCallback(accountId, getMessage(), {
			draftUID: draftUID,
			success: function () {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				// close composer
				if (sentCallback !== null) {
					sentCallback();
				} else {
					$('.message-composer').slideUp();
				}
				$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				newMessageBody.val('');
				newMessageBody.trigger('autosize.resize');
				attachments.reset();
				if (draftUID !== null) {
					// the sent message was a draft
					Mail.UI.messageView.collection.remove({id: draftUID});
					draftUID = null;
				}
			},
			error: function (jqXHR) {
				newMessageSend.prop('disabled', false);
				OC.Notification.showTemporary(jqXHR.responseJSON.message);
			},
			complete: function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				$('.mail-account').prop('disabled', false);
				to.prop('disabled', false);
				cc.prop('disabled', false);
				bcc.prop('disabled', false);
				subject.prop('disabled', false);
				$('.new-message-attachments-action').css('display', 'inline-block');
				$('#mail_new_attachment').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			}
		});
		return false;
	}

	function saveDraft(onSuccess) {
		clearTimeout(draftTimer);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// send the mail
		draftCallback(accountId, getMessage(), {
			draftUID: draftUID,
			success: function (data) {
				if (_.isFunction(onSuccess)) {
					onSuccess();
				}
				draftUID = data.uid;
			},
			error: function () {
				// TODO: show error
			}
		});
		return false;
	}

	return {
		events: events,
		initialize: initialize,
		render: render,
		changeAlias: changeAlias,
		handleKeyUp: handleKeyUp,
		handleKeyPress: handleKeyPress,
		submitMessage: submitMessage,

		/**
		 * Properties
		 */
		hasData: hasData
	};
})());
