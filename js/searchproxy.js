/* global OC, _ */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

var SearchProxy = {};

(function(OC, _) {
	'use strict';

	var filter = function() {};

	SearchProxy = {
		attach: function(search) {
			search.setFilter('mail', this.filterProxy);
		},
		filterProxy: function(query) {
			filter(query);
		},
		setFilter: function(newFilter) {
			filter = newFilter;
		}
	};

	if (!_.isUndefined(OC.Plugins)) {
		OC.Plugins.register('OCA.Search', SearchProxy);
	}

})(OC, _);
