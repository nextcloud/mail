/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

	var $ = require('jquery');
	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var MessageController = require('controller/messagecontroller');
	var MessageAttachmentTemplate = require('text!templates/message-attachment.html');

	/**
	 * @class MessageAttachmentView
	 */
	var MessageAttachmentView = Marionette.ItemView.extend({
		template: Handlebars.compile(MessageAttachmentTemplate),
		ui: {
			'downloadButton': '.attachment-download',
			'saveToCloudButton': '.attachment-save-to-cloud'
		},
		events: {
			'click': '_onDownload',
			'click @ui.saveToCloudButton': '_onSaveToCloud'
		},
		_onDownload: function(e) {
			if (!e.isDefaultPrevented()) {
				e.preventDefault();
				window.location = this.model.get('downloadUrl');
			}
		},
		_onSaveToCloud: function(e) {
			e.preventDefault();
			// TODO: 'message' should be a property of this attachment model
			// TODO: 'folder' should be a property of the message model and so on
			var account = require('state').currentAccount;
			var folder = require('state').currentFolder;
			var messageId = this.model.get('messageId');
			var attachmentId = this.model.get('id');
			var saving = MessageController.saveAttachmentToFiles(account, folder, messageId, attachmentId);

			// Loading feedback
			this.ui.saveToCloudButton.removeClass('icon-folder')
				.addClass('icon-loading-small')
				.prop('disabled', true);

			var _this = this;
			$.when(saving).always(function() {
				// Remove loading feedback again
				_this.ui.saveToCloudButton.addClass('icon-folder')
					.removeClass('icon-loading-small')
					.prop('disabled', false);
			});
		}
	});

	return MessageAttachmentView;
});
