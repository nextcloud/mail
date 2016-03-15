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

	var Backbone = require('backbone');

	/**
	 * @class Attachment
	 */
	var Attachment = Backbone.Model.extend({
		initialize: function() {
			this.set('id', _.uniqueId());

			var s = this.get('fileName');
			if (s.charAt(0) === '/') {
				s = s.substr(1);
			}

			this.set('displayName', s);
		}
	});

	return Attachment;
});
