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

	var Marionette = require('marionette');
	var $ = require('jquery');

	return Marionette.LayoutView.extend({
		el: $('#app'),
		regions: {
			settings: '#app-settings-content'
		},
		initialize: function() {
			this.bindUIElements();
		}
	});
});
