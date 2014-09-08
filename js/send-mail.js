/* global Mail, views */
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

	$(document).on('click', '.reply-message-send', function () {
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

		to.prop('disabled', true);
		cc.prop('disabled', true);

		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: Mail.State.currentAccountId}),
			beforeSend:function () {
			},
			complete:function () {
				replyMessageBody.removeClass('icon-loading');
				replyMessageBody.prop('disabled', false);
				replyMessageSend.prop('disabled', false);
				replyMessageSend.val(t('mail', 'Reply'));
				$('.reply-message-fields #to').prop('disabled', false);
				$('.reply-message-fields #cc').prop('disabled', false);
			},
			data:{
				to: to.val(),
				cc: cc.val(),
				folderId: Mail.State.currentFolderId,
				messageId: Mail.State.currentMessageId,
				body:replyMessageBody.val()
			},
			type: 'POST',
			success:function () {
				OC.msg.finishedAction('#reply-msg', {
					status: 'success',
					data: {
						message: t('mail', 'Mail sent to {Receiver}', {Receiver: to.val()})
					}
				});

				// close reply
				$('.reply-message-body').val('');
			},
			error: function() {
				Mail.UI.showError(t('mail', 'Error sending the reply.'));
			}
		});
	});


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
