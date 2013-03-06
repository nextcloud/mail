<?php
/**
 * Copyright (c) 2013 Thomas Müller
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Set the content type to Javascript
header("Content-type: text/javascript");

// Disallow caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

echo "
$(function () {
	$('#mail_editor').dialog({
		autoOpen:false,
		height:420,
		width:640,
		modal:true,
		resizable:false,
		show : 'fade',
		hide : 'drop',
		buttons:{
			'Send':function () {
				var dialog = $(this);
				$.ajax({
					url:OC.filePath('mail', 'ajax', 'send_message.php'),
					beforeSend:function () {
						$('#wait').show();
					},
					complete:function () {
						$('#wait').hide();
					},
					data:{
						'account_id': Mail.State.current_account_id,
						'to':$('#to').val(),
						'subject':$('#subject').val(),
						'body':$('#body').val()},
					success:function () {
						dialog.dialog('close');
					}
				});
				return false;
			}
		}});

	function split(val) {
		return val.split(/,\s*/);
	}

	function extractLast(term) {
		return split(term).pop();
	}";

	if (OCP\Contacts::isEnabled()) {
		echo "
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
	});";
	}

echo "});";
