/**
 * ownCloud - Mail
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
	var Handlebars = require('handlebars');
	var FolderView = require('views/folder');
	var AccountTemplate = require('text!templates/account.html');

	var SHOW_COLLAPSED = Object.seal([
		'inbox',
		'flagged',
		'drafts',
	]);

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		model: null,
		template: Handlebars.compile(AccountTemplate),
		templateHelpers: function() {
			var toggleCollapseMessage = this.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders');
			return {
				isUnifiedInbox: this.model.get('accountId') === -1,
				toggleCollapseMessage: toggleCollapseMessage
			};
		},
		collapsed: true,
		events: {
			'click .account-toggle-collapse': 'toggleCollapse'
		},
		childView: FolderView,
		childViewContainer: '#mail_folders',
		initialize: function(options) {
			this.model = options.model;
			this.collection = this.model.get('folders');
		},
		filter: function(child) {
			if (!this.collapsed) {
				return true;
			}
			var specialRole = child.get('specialRole');
			return SHOW_COLLAPSED.indexOf(specialRole) !== -1;
		},
		toggleCollapse: function() {
			this.collapsed = !this.collapsed;
			this.render();
		}
	});
});
