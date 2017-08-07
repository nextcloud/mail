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
	'service/attachmentservice',
	'OC',
	'radio',
], function(AttachmentService, OC, Radio) {

	describe('AttachmentService', function() {

		var fakeModel;
		var fakeFile;

		beforeEach(function() {
			jasmine.Ajax.install();
			fakeModel = jasmine.createSpyObj('fakeModel', ['onProgress', 'set', 'get', 'unset']);
			fakeFile = {filename: 'file.zip', size: '12345' };
		});

		afterEach(function() {
			jasmine.Ajax.uninstall();
		});

		/* when everything went as expected on attachment upload */
		it('uploads a new attachment on the server', function() {
			spyOn(OC, 'generateUrl').and.returnValue('index.php/apps/mail/attachments');

			jasmine.Ajax.stubRequest('index.php/apps/mail/attachments').andReturn({
				status: 200,
				contentType: 'application/json',
				responseText: '{"id": "33", "fileName": "file.zip"}'
			});

			var promise = AttachmentService.uploadLocalAttachment(fakeFile, fakeModel);
			expect(OC.generateUrl).toHaveBeenCalledWith('/apps/mail/attachments');

			promise
				.then(function(attachment, fileId) {
					expect(jasmine.Ajax.requests.count()).toBe(1);
					expect(jasmine.Ajax.requests.mostRecent().url).toBe('index.php/apps/mail/attachments');
				})
				.catch(function(attachment) {
					fail('Attachment upload is not supposed to fail');
				});
		});

		/* when an error occurred on server side on attachment upload */
		it('handles errors during attachment uploads', function() {
			spyOn(OC, 'generateUrl').and.returnValue('index.php/apps/mail/attachments');

			jasmine.Ajax.stubRequest('index.php/apps/mail/attachments').andReturn({
				status: 500,
				contentType: 'text/plain'
			});

			var promise = AttachmentService.uploadLocalAttachment(fakeFile, fakeModel);
			expect(OC.generateUrl).toHaveBeenCalledWith('/apps/mail/attachments');

			promise
				.then(function(attachment, fileId) {
					fail('Attachment upload is supposed to fail');
				})
				.catch(function(attachment) {
					expect(jasmine.Ajax.requests.count()).toBe(1);
					expect(jasmine.Ajax.requests.mostRecent().url).toBe('index.php/apps/mail/attachments');
				});
		});

		/* when upload finished with success */
		it('handles upload finished with success', function() {
			// progress=1, for upload succeeded
			fakeModel.progress = 1;
			AttachmentService.uploadLocalAttachmentFinished(fakeModel, 33);

			// the model ID and upload status are updated on succes
			expect(fakeModel.set).toHaveBeenCalledWith('id', 33);
			expect(fakeModel.set).toHaveBeenCalledWith('uploadStatus', 3);

			// make sure the xhr request has been removed from the model
			expect(fakeModel.unset).toHaveBeenCalledWith('uploadRequest');

		});

		/* when a problem occured on upload */
		it('handles upload finished with error', function() {
			// progress=1, but no id returned from the server
			fakeModel.progress = 1;
			AttachmentService.uploadLocalAttachmentFinished(fakeModel);
			// the upload status are updated on succes
			expect(fakeModel.set).toHaveBeenCalledWith('uploadStatus', 2);
			// make sure the xhr request has been removed from the model
			expect(fakeModel.unset).toHaveBeenCalledWith('uploadRequest');

			// == id returned from server but file not uploaded ==
			// not sure that is possible though
			fakeModel.progress = 0.5;
			AttachmentService.uploadLocalAttachmentFinished(fakeModel, 33);
			// the upload status are updated on succes
			expect(fakeModel.set).toHaveBeenCalledWith('uploadStatus', 2);
			// make sure the xhr request has been removed from the model
			expect(fakeModel.unset).toHaveBeenCalledWith('uploadRequest');
		});
	});
});
