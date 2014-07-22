/* global Mail */
$(function () {

	function split(val) {
		return val.split(/,\s*/);
	}

	function extractLast(term) {
		return split(term).pop();
	}
	$('#to')
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
					OC.filePath('mail', 'ajax', 'receivers.php'),
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
		$('.reply-message-body').addClass('icon-loading');
		$('.reply-message-body').prop('disabled', true);
		$('.reply-message-send').prop('disabled', true);
		$('.reply-message-send').val(t('mail', 'Sending …'));

		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: Mail.State.currentAccountId}),
			beforeSend:function () {
			},
			complete:function () {
			},
			data:{
				'folderId': Mail.State.currentFolderId,
				'messageId': Mail.State.currentMessageId,
				'body':$('.reply-message-body').val()
			},
			success:function () {
				// close reply
				$('.reply-message-body').val('');
			}
		});
	});

	$(document).on('click', '#new-message-send', function ()
	{
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// loading feedback: show spinner and disable elements
		$('#new-message-body').addClass('icon-loading');
		$('#to').prop('disabled', true);
		$('#subject').prop('disabled', true);
		$('#new-message-body').prop('disabled', true);
		$('#new-message-send').prop('disabled', true);
		$('#new-message-send').val(t('mail', 'Sending …'));

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
				'subject':$('#subject').val(),
				'body':$('#new-message-body').val()
			},
			success:function () {
				// close composer
				$('#new-message-fields').slideUp();
				$('#mail_new_message').fadeIn();
			}
		});

		return false;
	});


});
