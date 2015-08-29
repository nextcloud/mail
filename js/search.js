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

	return {
		timeoutID: null,
		attach: function(search) {
			search.setFilter('mail', require('app').Search.filter);
		},
		filter: function(query) {
			window.clearTimeout(require('app').Search.timeoutID);
			require('app').Search.timeoutID = window.setTimeout(function() {
				require('app').UI.messageView.filterCurrentMailbox(query);
			}, 500);
			$('#searchresults').hide();
		}
	};
});
