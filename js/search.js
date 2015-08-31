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

	var timeoutID = null;
	function attach(search) {
		search.setFilter('mail', require('app').Search.filter);
	}

	function filter(query) {
		window.clearTimeout(timeoutID);
		timeoutID = window.setTimeout(function() {
			require('app').UI.messageView.filterCurrentMailbox(query);
		}, 500);
		$('#searchresults').hide();
	}

	return {
		attach: attach,
		filter: filter
	};
});
