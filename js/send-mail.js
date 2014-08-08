/* global Mail, views */
$(function () {

	function split(val) {
		return val.split(/,\s*/);
	}

	function extractLast(term) {
		return split(term).pop();
	}
	$('#to,#cc,#bcc')
		// don't navigate away from the field on tab when selecting an item
		.bind('keydown', function (event) {
			if (event.keyCode === $.ui.keyCode.TAB &&
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

		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: Mail.State.currentAccountId}),
			beforeSend:function () {
			},
			complete:function () {
			},
			data:{
				'folderId': Mail.State.currentFolderId,
				'messageId': Mail.State.currentMessageId,
				'body':replyMessageBody.val()
			},
			type: 'POST',
			success:function () {
				// close reply
				$('.reply-message-body').val('');
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

	// setup sendmail view
	var view = new views.SendMail({
		el: $('#new-message')
	});

	// And render it
	view.render();
});
