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

	return Backbone.Marionette.ItemView.extend({
		template: '#mail-folder-template',
		events: {
			'click .collapse': 'collapseFolder',
			'click .folder': 'loadFolder'
		},
		initialize: function(options) {
			this.model = options.model;
		},
		collapseFolder: function(e) {
			e.preventDefault();
			this.model.toggleOpen();
		},
		loadFolder: function(e) {
			e.preventDefault();
			var accountId = this.model.get('accountId');
			var folderId = $(e.currentTarget).parent().data('folder_id');
			var noSelect = $(e.currentTarget).parent().data('no_select');
			require('app').UI.loadFolder(accountId, folderId, noSelect);
		},
		onRender: function() {
			// Get rid of that pesky wrapping-div.
			// Assumes 1 child element present in template.
			this.$el = this.$el.children();
			// Unwrap the element to prevent infinitely
			// nesting elements during re-render.
			this.$el.unwrap();
			this.setElement(this.$el);
		}
	});
});
