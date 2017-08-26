/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var Marionette = require('backbone.marionette');
	var MessageController = require('controller/messagecontroller');
	var AttachmentView = require('views/messageattachment');
	var AttachmentsTemplate = require('handlebars-loader!templates/message-attachments.html');

	/**
	 * @type MessageAttachmentsView
	 */
	var MessageAttachmentsView = Marionette.CompositeView.extend({
		/**
		 * @lends Marionette.CompositeView
		 */
		template: AttachmentsTemplate,
		ui: {
			'saveAllToCloud': '.attachments-save-to-cloud'
		},
		events: {
			'click @ui.saveAllToCloud': '_onSaveAllToCloud'
		},
		templateContext: function() {
			return {
				moreThanOne: this.collection.length > 1
			};
		},
		childView: AttachmentView,
		childViewContainer: '.attachments',
		initialize: function(options) {
			this.message = options.message;
		},
		_onSaveAllToCloud: function(e) {
			e.preventDefault();

			// TODO: 'message' should be a property of this attachment model
			// TODO: 'folder' should be a property of the message model and so on
			var account = require('state').currentAccount;
			var folder = require('state').currentFolder;
			var messageId = this.message.get('id');
			// Loading feedback
			this.getUI('saveAllToCloud').removeClass('icon-folder')
				.addClass('icon-loading-small')
				.prop('disabled', true);

			var _this = this;
			MessageController.saveAttachmentsToFiles(account, folder, messageId)
				.catch(console.error.bind(this)).then(function() {
				// Remove loading feedback again
				_this.getUI('saveAllToCloud').addClass('icon-folder')
					.removeClass('icon-loading-small')
					.prop('disabled', false);
			});
		}
	});

	return MessageAttachmentsView;
});
