/* global expect */

/**
 * @author Luc Calaresu <dev@calaresu.com>
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


define([
	'views/attachmentview',
	'models/localattachment',
	'models/attachment',
	'radio'
], function(AttachmentView, LocalAttachment, Attachment, Radio) {

	describe('AttachmentView', function() {

		var view;
		var model;

		describe('on local attachment', function() {

			beforeEach(function() {
				// on local attachment, we use the LocalAttachment model
				model = new LocalAttachment({
					fileName: 'test.zip',
					uploadRequest: true
				});
				view = new AttachmentView({
					model: model
				});
				view.render();
				view.bindUIElements();
			});

			it('should exist', function() {
				expect(view).toBeDefined();
			});

			it('should have a progress bar set at 0', function() {
				// jquery-ui progressbar is initialised
				expect(view.ui.attachmentName.attr('role')).toBe('progressbar');
				// value of progressbar is set to 0
				expect(view.ui.attachmentName.attr('aria-valuenow')).toBe('0');
			});

			it('should allow attachment removal', function() {
				/* We just test that the 'upload:abort' event has been sent */
				/* The deletion action itself is tested on the attachmentService tests */
				spyOn(Radio.attachment, 'request');
				view.removeAttachment();
				expect(Radio.attachment.request).toHaveBeenCalledWith('upload:abort', model);
			});

			it('should update the attachment colour on upload status changes', function() {
				// Update the model progress value and make sure the css class has been added
				model.set('uploadStatus', 1); // uploading
				expect(view.ui.attachmentName.attr('class')).toContain('upload-ongoing');

				model.set('uploadStatus', 2); // error
				expect(view.ui.attachmentName.attr('class')).not.toContain('upload-ongoing');
				expect(view.ui.attachmentName.attr('class')).toContain('upload-warning');

				model.set('uploadStatus', 3); // success
				expect(view.ui.attachmentName.attr('class')).not.toContain('upload-ongoing');
				expect(view.ui.attachmentName.attr('class')).not.toContain('upload-warning');
			});

			it('should update the progress bar on model progress changes', function() {
				spyOn(view.ui.attachmentName, 'progressbar');
				// Update the model progress value and make sure the view has been updated
				model.set('progress', 0.5);
				expect(view.ui.attachmentName.progressbar).toHaveBeenCalledWith(
					'option', 'value', 0.5
					);
			});
		});

		describe('on attachment from Files', function() {

			beforeEach(function() {
				// on attachment from Files, we use the Attachment model
				model = new Attachment({
					fileName: 'test.zip'
				});
				view = new AttachmentView({
					model: model
				});
				view.render();
			});

			it('should exist', function() {
				expect(view).toBeDefined();
			});

			it('should not have a progress bar', function() {
				// jquery-ui progressbar is NOT initialised
				expect(view.ui.attachmentName.attr('role')).toBe(undefined);
				// and css style upload-ongoing has NOT been added
				expect(view.ui.attachmentName.attr('class')).not.toContain('upload-ongoing');
			});

			it('should allow attachment removal', function() {
				/* We just test that the 'upload:abort' event has been sent */
				/* The deletion action itself is tested on the attachmentService tests */
				spyOn(Radio.attachment, 'request');
				view.removeAttachment();
				expect(Radio.attachment.request).toHaveBeenCalledWith('upload:abort', model);
			});
		});

	});
});
