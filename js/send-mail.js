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
				if (term.length < 2) {
					return false;
				}
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
		//  - disable fields
		//  - loading animation
		//  - input validation
		//  - fadeout on success
		//  - undo lie - very important
		//

		$.ajax({
			url:OC.filePath('mail', 'ajax', 'reply_to.php'),
			beforeSend:function () {
			},
			complete:function () {
			},
			data:{
				'account_id': Mail.State.current_account_id,
				'folder_id': Mail.State.current_folder_id,
				'message_id': Mail.State.current_message_id,
				'body':$('.reply-message-body').val()},
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
		//  - disable fields
		//  - loading animation
		//  - input validation
		//  - fadeout on success
		//  - undo lie - very important
		//

		// send the mail
		$.ajax({
			url:OC.filePath('mail', 'ajax', 'send_message.php'),
			beforeSend:function () {
//				$('#wait').show();
			},
			complete:function () {
//				$('#wait').hide();
			},
			data:{
				'account_id': Mail.State.current_account_id,
				'to':$('#to').val(),
				'subject':$('#subject').val(),
				'body':$('#body').val()},
			success:function () {
				// close composer
				$('#new-message-fields').slideUp();
				$('#mail_new_message').fadeIn();
			}
		});

		return false;
	});


});
