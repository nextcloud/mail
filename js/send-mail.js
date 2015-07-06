/* global OC */
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

});
