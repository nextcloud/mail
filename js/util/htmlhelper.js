/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function() {
	'use strict';

	function htmlToText(html) {
		var breakToken = '__break_token__';
		// Preserve line breaks
		html = html.replace(/<br>/g, breakToken);
		html = html.replace(/<br\/>/g, breakToken);

		// Add <br> break after each closing div, p, li to preserve visual
		// line breaks for replies
		html = html.replace(/(<\/div>)([^$]?)/g, '\$1' + breakToken + '\$2');
		html = html.replace(/(<\/p>)([^$]?)/g, '\$1' + breakToken + '\$2');
		html = html.replace(/(<\/li>)([^$]?)/g, '\$1' + breakToken + '\$2');

		var tmp = $('<div>');
		tmp.html(html);
		var text = tmp.text();

		// Finally, replace tokens with line breaks
		text = text.replace(new RegExp(breakToken, 'g'), '\n');
		return text.trim();
	}

	return {
		htmlToText: htmlToText
	};
});
