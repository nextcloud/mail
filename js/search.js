/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var Radio = require('radio');
	var lastQuery = '';

	var debouncedFilter = _.debounce(function debouncedFilterFn(query) {
		Radio.ui.trigger('messagesview:filter', query);
	}, 1000);

	function filter(query) {
		if (query !== lastQuery) {
			lastQuery = query;
			debouncedFilter(query);
		}
	}

	return {
		filter: filter
	};
});
