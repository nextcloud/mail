/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var htmlToTextLib = require('html-to-text');

	function htmlToText(html) {
		return htmlToTextLib.fromString(html, {
			noLinkBrackets: true,
			ignoreImage: true
		});
	}

	return {
		htmlToText: htmlToText
	};
});
