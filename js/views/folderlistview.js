/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var FolderView = require('views/folderview');

	var SHOW_COLLAPSED = Object.seal([
		'inbox',
		'flagged',
		'drafts',
		'sent'
	]);

	var FolderListView = Marionette.CollectionView.extend({
		tagName: 'ul',
		childView: FolderView,
		collapsed: true,
		initialize: function(options) {
			this.collapsed = options.collapsed;
		},
		filter: function(child) {
			if (!this.collapsed) {
				return true;
			}
			var specialRole = child.get('specialRole');
			return SHOW_COLLAPSED.indexOf(specialRole) !== -1;
		}
	});

	return FolderListView;
});
