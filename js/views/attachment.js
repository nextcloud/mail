/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var Radio = require('radio');
	var AttachmentTemplate = require('text!templates/attachment.html');

	return Marionette.View.extend({
		tagName: 'li',
		template: Handlebars.compile(AttachmentTemplate),
		ui: {
			attachmentName: '.new-message-attachment-name'
		},
		events: {
			'click .icon-delete': 'removeAttachment'
		},
		modelEvents: {
			'change:progress': 'onProgress',
			'change:uploadStatus': 'onUploadStatus'
		},
		onRender: function() {
			var uploadRequest = this.model.get('uploadRequest');
			if (uploadRequest) {
				/* If upload, init the progressbar with the initial and max value  */
				this.ui.attachmentName.progressbar({value: 0, max: 1});
				// Remove two jQuery styling classes that make it ugly
				// and add a blue text while uploading
				this.ui.attachmentName
					.removeClass('ui-progressbar')
					.removeClass('ui-widget-content')
					.addClass('upload-ongoing');
			}
		},

		/**
		 * Called when the user clicked on the wastebasket.
		 */
		removeAttachment: function() {
			/* If we are trying to delete a still-uploading attachment, */
			/* we have to abort the request first */
			Radio.attachment.request('upload:abort', this.model);
			this.model.collection.remove(this.model);
		},

		/**
		 * Triggered when the attachment progress value changed
		 */
		onProgress: function() {
			/* Update the ProgressBar with the new model value */
			var progressValue = this.model.get('progress');
			this.ui.attachmentName.progressbar('option', 'value', progressValue);
		},

		/**
		 * Triggered when the attachment upload status has changed
		 */
		onUploadStatus: function() {
			switch (this.model.get('uploadStatus')) {
				case 1:     // uploading
					this.ui.attachmentName.addClass('upload-ongoing');
					break;
				case 2:     // error
					/* An error occurred, we make the filename and the progressbar red */
					this.ui.attachmentName
						.removeClass('upload-ongoing')
						.addClass('upload-warning');
					break;
				case 3:     // success
					/* remove the 'ongoing' class  */
					this.ui.attachmentName.removeClass('upload-ongoing');
					/* If everything went well, we just fade out the progressbar */
					this.ui.attachmentName.find('.ui-progressbar-value').fadeOut();
					break;
			}
		}
	});
});
