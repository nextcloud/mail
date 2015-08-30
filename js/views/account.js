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
	var FolderView = require('views/folder');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		model: null,
		template: '#mail-account-template',
		childView: FolderView,
		childViewContainer: '#mail_folders',
		initialize: function(options) {
			this.model = options.model;
			this.collection = this.model.get('folders');
		}

	});
});
