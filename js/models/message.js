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
		folder: undefined,
		defaults: {
			flags: [],
			active: false,
			from: [],
			to: [],
			cc: [],
			bcc: []
		},
		initialize: function() {
			this.set('flags', new MessageFlags(this.get('flags')));
			if (this.get('folder')) {
				// Folder should be a simple property
				this.folder = this.get('folder');
				this.unset('folder');
			}
			this.listenTo(this.get('flags'), 'change', this._transformEvent);
			this.set('dateMicro', this.get('dateInt') * 1000);
		},
		_transformEvent: function() {
			this.trigger('change');
			this.trigger('change:flags', this);
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
