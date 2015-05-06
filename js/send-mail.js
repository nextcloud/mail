/* global Mail, OC */
$(function () {

	function split(val) {
		return val.split(/,\s*/);
	}

	function extractLast(term) {
		return split(term).pop();
	}
	$(document).on('focus', '.recipient-autocomplete', function() {
		if ( !$(this).data("autocomplete") ) { // If the autocomplete wasn't called yet:

			// don't navigate away from the field on tab when selecting an item
			$(this)
			.bind('keydown', function (event) {
				if (event.keyCode === $.ui.keyCode.TAB &&
					typeof $(this).data('autocomplete') !== 'undefined' &&
					$(this).data('autocomplete').menu.active) {
					event.preventDefault();
				}
			})
			.autocomplete({
				source:function (request, response) {
					$.getJSON(
						OC.generateUrl('/apps/mail/accounts/autoComplete'),
						{
							term:extractLast(request.term)
						}, response);
				},
				search:function () {
					// custom minLength
					var term = extractLast(this.value);
					return term.length >= 2;

				},
				focus:function () {
					// prevent value inserted on focus
					return false;
				},
				select:function (event, ui) {
					var terms = split(this.value);
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push(ui.item.value);
					// add placeholder to get the comma-and-space at the end
					terms.push('');
					this.value = terms.join(", ");
					return false;
				}
			});
		}
	});

	function getReplyMessage() {
		var message = {};

		var replyMessageBody = $('.reply-message-body');
		var to = $('.reply-message-fields #to');
		var cc = $('.reply-message-fields #cc');
		message.body = replyMessageBody.val();
		message.to = to.val();
		message.cc = cc.val();

		return message;
	}

	function saveReplyLocally() {
		if (Mail.State.currentMessageId === null) {
			// new message
			return;
		}
		var storage = $.localStorage;
		storage.set('draft' +
			'.' + Mail.State.currentAccountId.toString() +
			'.' + Mail.State.currentFolderId.toString() +
			'.' + Mail.State.currentMessageId.toString(),
			getReplyMessage());
	}

	function sendReply() {
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// loading feedback: show spinner and disable elements
		var replyMessageBody = $('.reply-message-body');
		var replyMessageSend = $('.reply-message-send');
		replyMessageBody.addClass('icon-loading');
		replyMessageBody.prop('disabled', true);
		replyMessageSend.prop('disabled', true);
		replyMessageSend.val(t('mail', 'Sending …'));
		var to = $('.reply-message-fields #to');
		var cc = $('.reply-message-fields #cc');
		var messageId = Mail.State.currentMessageId;

		to.prop('disabled', true);
		cc.prop('disabled', true);

		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: Mail.State.currentAccountId}),
			beforeSend:function () {
			},
			complete:function () {
				replyMessageBody.removeClass('icon-loading');
				replyMessageBody.prop('disabled', false);
				replyMessageSend.val(t('mail', 'Reply'));
				$('.reply-message-fields #to').prop('disabled', false);
				$('.reply-message-fields #cc').prop('disabled', false);
			},
			data:{
				to: to.val(),
				cc: cc.val(),
				folderId: Mail.State.currentFolderId,
				messageId: messageId,
				body:replyMessageBody.val()
			},
			type: 'POST',
			success:function () {
				Mail.State.messageView.setMessageFlag(messageId, 'answered', true);
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				// close reply
				replyMessageBody.val('');
				replyMessageBody.trigger('autosize.resize');
			},
			error: function() {
				Mail.UI.showError(t('mail', 'Error sending the reply.'));
			}
		});
	}

	$(document).on('keypress', '.reply-message-body', function(event) {
		// check for ctrl+enter
		if (event.keyCode === 13 && event.ctrlKey) {
			var sendBtnState = $('.reply-message-send').attr('disabled');
			if (sendBtnState === undefined) {
				sendReply();
			}
		}
	});

	$(document).on('keyup', '.reply-message-body, #to, #cc', saveReplyLocally);

	$(document).on('click', '.reply-message-send', sendReply);

	// cc/bcc toggling
	$(document).on('click', '#new-message-cc-bcc-toggle', function() {
		$('#new-message-cc-bcc').slideToggle();
		$('#new-message-cc-bcc #cc').focus();
		$('#new-message-cc-bcc-toggle').fadeOut();
	});

	$(document).on('click', '#reply-message-cc-bcc-toggle', function() {
		$('#reply-message-cc-bcc').slideToggle();
		$('#reply-message-cc-bcc #cc').focus();
		$('#reply-message-cc-bcc-toggle').fadeOut();
	});
});
