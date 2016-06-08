/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

define(function(require) {
	'use strict';

	var Backbone = require('backbone');

	/**
	 * @class Alias
	 */
	var Alias = Backbone.Model.extend({
		defaults: {
		},
		initialize: function() {

		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.alias && data.alias.toJSON) {
				data.alias = data.alias.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Alias;
});
