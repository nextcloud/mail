/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Radio = require('radio');
	var timeoutID = null;

	function filter(query) {
		window.clearTimeout(timeoutID);
		timeoutID = window.setTimeout(function() {
			Radio.ui.trigger('messagesview:filter', query);
		}, 500);
		$('#searchresults').hide();
	}

	return {
		filter: filter
	};
});
