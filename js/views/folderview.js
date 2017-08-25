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

	var _ = require('underscore');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var OC = require('OC');
	var Radio = require('radio');
	var FolderTemplate = require('raw-loader!templates/folder.html');

	return Marionette.View.extend({

		tagName: 'li',

		updateElClasses: function() {
			var classes = [];
			if (this.model.get('unseen')) {
				classes.push('unseen');
			}
			if (this.model.get('active')) {
				classes.push('active');
			}
			if (this.model.get('specialRole')) {
				classes.push('special-' + this.model.get('specialRole'));
			}
			if (this.model.folders.length > 0) {
				classes.push('collapsible');
			}
			if (this.model.get('open')) {
				classes.push('open');
			}
			// .removeClass() does not work, https://bugs.jqueryui.com/ticket/9015
			this.$el.prop('class', '');
			var _this = this;
			_.each(classes, function(clazz) {
				_this.$el.addClass(clazz);
			});
		},

		template: Handlebars.compile(FolderTemplate),

		templateContext: function() {
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

			var folders = this.model.folders.length > 0 ? this.model.folders.toJSON() : undefined;

			return {
				count: count,
				url: url,
				folders: folders
			};
		},

		regions: {
			folders: '.folders'
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
			var folder = this.model;

			if (folder.get('noSelect')) {
				console.info('ignoring \'loadFolder\' event for noSelect folder');
				return;
			}

			Radio.navigation.trigger('folder', account.get('accountId'), folder.get('id'));
		},

		onRender: function() {
			var FolderListView = require('views/folderlistview');

			this.showChildView('folders', new FolderListView({
				collection: this.model.folders
			}));

			this.updateElClasses();

			// Make non search folder folders droppable
			if (!(/\/FLAGGED$/.test(atob(this.model.get('id'))))) {
				var dropScope = 'folder-' + this.model.account.get('accountId');
				this.$el.droppable({
					scope: dropScope,
					greedy: true,
					drop: _.bind(function(event, ui) {
						var account = require('state').currentAccount;
						var sourceFolder = account.getFolderById(ui.helper.data('folderId'));
						var message = sourceFolder.messages.get(ui.helper.data('messageId'));
						Radio.message.trigger('move', account, sourceFolder, message, account, this.model);
					}, this),
					hoverClass: 'ui-droppable-active'
				});
			}
		}
	});
});
