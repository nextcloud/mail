/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var Marionette = require('backbone.marionette');
	var NoSearchResultMessageListViewTemplate
		= require('templates/no-search-results-message-list.html');

	return Marionette.View.extend({
		initialize: function(options) {
			this.model.set('searchTerm', options.searchQuery);
		},
		template: NoSearchResultMessageListViewTemplate
	});
});

