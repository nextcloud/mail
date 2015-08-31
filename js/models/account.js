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
	var FolderCollection = require('models/foldercollection');
	var OC = require('OC');

	return Backbone.Model.extend({
		defaults: {
			folders: []
		},
		url: OC.generateUrl('apps/mail/accounts'),
		initialize: function() {
			this.set('folders', new FolderCollection(this.get('folders')));
		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.folders && data.folders.toJSON) {
				data.folders = data.folders.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});
});
