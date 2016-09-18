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

	var Backbone = require('backbone');
	var _ = require('underscore');

	/**
	 * @class Attachment
	 */
	var Attachment = Backbone.Model.extend({
		initialize: function() {
			if (_.isUndefined(this.get('id'))) {
				this.set('id', _.uniqueId());
			}

			var s = this.get('fileName');

			if (_.isUndefined(s)) {
				return;
			}

			if (s.charAt(0) === '/') {
				s = s.substr(1);
			}

			this.set('displayName', s);
		}
	});

	return Attachment;
});
