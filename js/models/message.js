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

	var Backbone = require('backbone'),
		$ = require('jquery'),
		OC = require('OC'),
		MessageFlags = require('models/messageflags');

	return Backbone.Model.extend({
		defaults: {
			flags: [],
			active: false
		},
		initialize: function() {
			this.set('flags', new MessageFlags(this.get('flags')));
			this.listenTo(this.get('flags'), 'change', this._transformEvent);
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
		},
		flagMessage: function(flag, value) {
			var messageId = this.id;
			var thisModel = this;
			thisModel.get('flags').set(flag, value);

			var flags = [flag, value];
			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/flags',
					{
						accountId: require('app').State.currentAccountId,
						folderId: require('app').State.currentFolderId,
						messageId: messageId
					}), {
				data: {
					flags: _.object([flags])
				},
				type: 'PUT',
				success: function() {
				},
				error: function() {
					require('app').UI.showError(t('mail', 'Message could not be starred. Please try again.'));
					thisModel.get('flags').set(flag, !value);
				}
			});
		}

	});
});
