/**
 * Mail
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
	var MessageFlags = require('models/messageflags');

	/**
	 * @class Message
	 */
	var Message = Backbone.Model.extend({
		defaults: {
			flags: [],
			active: false,
			hasDetails: false,
		},
		initialize: function() {
			this.set('flags', new MessageFlags(this.get('flags')));
			this.on('change:flags', this._mergeFlags);
			this.listenTo(this.get('flags'), 'change', this._transformEvent);
		},
		_transformEvent: function() {
			this.trigger('change');
			this.trigger('change:flags', this);
		},
		_mergeFlags: function(model, value) {
			var oldFlags = this.previousAttributes()['flags'];
			oldFlags.set(value, {silent: true}); // Merge changes
			this.set('flags', oldFlags);
		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.flags && data.flags.toJSON) {
				data.flags = data.flags.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Message;
});
