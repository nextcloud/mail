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

	var $ = require('jquery');
	var Backbone = require('backbone');
	var Handlebars = require('handlebars');
	var OC = require('OC');
	var Radio = require('radio');
	var FolderTemplate = require('text!templates/folder.html');

	return Backbone.Marionette.View.extend({
		template: Handlebars.compile(FolderTemplate),
		templateHelpers: function() {
			var count = null;
			if (this.model.get('specialRole') === 'drafts') {
				count = this.model.get('total');
			} else {
				count = this.model.get('unseen');
			}

			var url = OC.generateUrl('apps/mail/#accounts/{accountId}/folders/{folderId}', {
				// TODO: account should be property of folder
				accountId: this.model.get('accountId'),
				folderId: this.model.get('id')
			});

			return {
				count: count,
				url: url
			};
		},
		events: {
			'click .collapse': 'collapseFolder',
			'click .folder': 'loadFolder'
		},
		modelEvents: {
			change: 'render'
		},
		collapseFolder: function(e) {
			e.preventDefault();
			this.model.toggleOpen();
		},
		loadFolder: function(e) {
			e.preventDefault();
			// TODO: account should be property of folder
			var account = require('state').accounts.get(this.model.get('accountId'));
			var folderId = $(e.currentTarget).parent().data('folder_id');
			var folder = null;
			if (folderId === this.model.get('id')) {
				folder = this.model;
			} else {
				folder = this.model.folders.get(folderId);
			}
			var noSelect = $(e.currentTarget).parent().data('no_select');
			Radio.navigation.trigger('folder', account.get('accountId'), folder.get('id'), noSelect);
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
