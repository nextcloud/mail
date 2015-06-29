/* global Mail, OC */
$(function() {

	function split(val) {
		return val.split(/,\s*/);
	}

	function extractLast(term) {
		return split(term).pop();
	}
	$(document).on('focus', '.recipient-autocomplete', function() {
		if (!$(this).data('autocomplete')) { // If the autocomplete wasn't called yet:

			// don't navigate away from the field on tab when selecting an item
			$(this)
			.bind('keydown', function(event) {
				if (event.keyCode === $.ui.keyCode.TAB &&
					typeof $(this).data('autocomplete') !== 'undefined' &&
					$(this).data('autocomplete').menu.active) {
					event.preventDefault();
				}
			})
			.autocomplete({
				source:function(request, response) {
					$.getJSON(
						OC.generateUrl('/apps/mail/accounts/autoComplete'),
						{
							term:extractLast(request.term)
						}, response);
				},
				search:function() {
					// custom minLength
					var term = extractLast(this.value);
					return term.length >= 2;

				},
				focus:function() {
					// prevent value inserted on focus
					return false;
				},
				select:function(event, ui) {
					var terms = split(this.value);
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push(ui.item.value);
					// add placeholder to get the comma-and-space at the end
					terms.push('');
					this.value = terms.join(', ');
					return false;
				}
			});
		}
	});

	$(document).on('keypress', '.reply-message-body', function(event) {
		// Define which objects to check for the event properties.
		// (Window object provides fallback for IE8 and lower.)
		event = event || window.event;
		var key = event.keyCode || event.which;
		// If enter and control keys are pressed:
		// (Key 13 and 10 set for compatibility across different operating systems.)
		if ((key === 13 || key === 10) && event.ctrlKey) {
			// If the new message is completely filled, and ready to be sent:
			// Send the reply.
			var sendBtnState = $('.reply-message-send').attr('disabled');
			if (sendBtnState === undefined) {
				sendReply();
			}
		}
	});

});
