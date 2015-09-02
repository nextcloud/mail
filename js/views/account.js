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
	var Handlebars = require('handlebars');
	var FolderView = require('views/folder');
	var AccountTemplate = require('text!templates/account.html');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		model: null,
		template: Handlebars.compile(AccountTemplate),
		childView: FolderView,
		childViewContainer: '#mail_folders',
		initialize: function(options) {
			this.model = options.model;
			this.collection = this.model.get('folders');
		}

	});
});
