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
	var Message = require('models/message');

	/**
	 * @class MessageCollection
	 */
	var MessageCollection = Backbone.Collection.extend({
		model: Message,
		comparator: function(message) {
			return message.get('dateInt') * -1;
		}
	});

	return MessageCollection;
});
