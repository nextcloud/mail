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

	var CrashReport = require('crashreport');
	var Marionette = require('backbone.marionette');
	var MessageController = require('controller/messagecontroller');
	var AttachmentView = require('views/messageattachmentview');
	var AttachmentsTemplate = require('templates/message-attachments.html');

	/**
	 * @type MessageAttachmentsView
	 */
	var MessageAttachmentsView = Marionette.CompositeView.extend({
		/**
		 * @lends Marionette.CompositeView
		 */
		template: AttachmentsTemplate,
		ui: {
			'saveAllToCloud': '.attachments-save-to-cloud',
			'downloadAll': '.attachments-download-all'
		},
		events: {
			'click @ui.saveAllToCloud': '_onSaveAllToCloud',
			'click @ui.downloadAll': '_onDownloadAll'
		},
		templateContext: function() {
			return {
				moreThanOne: this.collection.length > 1
			};
		},
		childView: AttachmentView,
		childViewContainer: '.attachments',
		childViewOptions: function() {
			return {
				message: this.message
			};
		},
		initialize: function(options) {
			this.message = options.message;
		},

		viewComparator: function(a, b) {
			if (a.get('isImage') && !b.get('isImage')) {
				return -1;
			} else if (!a.get('isImage') && b.get('isImage')) {
				return 1;
			}
			return a.get('fileName').localeCompare(b.get('fileName'));
		},

		_onSaveAllToCloud: function(e) {
			e.preventDefault();

			var _this = this;
			MessageController.saveAttachmentsToFiles(this.message, function() {
				// Loading feedback
				_this.getUI('saveAllToCloud').removeClass('icon-folder')
				.addClass('icon-loading-small')
				.prop('disabled', true);
			}).catch(CrashReport.report).then(function() {
				// Remove loading feedback again
				_this.getUI('saveAllToCloud').addClass('icon-folder')
					.removeClass('icon-loading-small')
					.prop('disabled', false);
			});
		},

		_onDownloadAll: function(e){
			e.preventDefault();

			window.open(this.message.get('downloadAllAttachmentsUrl'));
			window.focus();
		}
	});

	return MessageAttachmentsView;
});
