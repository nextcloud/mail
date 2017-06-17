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
	'models/localattachment'
], function(LocalAttachment) {
	/* LocalAttachment derivates from Attachment */
	/* We just test the specifics since Attachment is already tested */
	describe('LocalAttachment', function() {
		var attachment;

		it('has an initial progress and status equal to 0', function() {
			var a1 = new LocalAttachment({
				fileName: '/file1.png'
			});

			expect(a1.get('progress')).toBe(0);
			expect(a1.get('uploadStatus')).toBe(0);
		});

		it('updates its attributes on upload progress', function() {
			attachment = new LocalAttachment();
			/* simulate a call to 'onProgress' with some example values */
			progressEvent = {
				lengthComputable: true,
				loaded: 500,
				total: 1000
			}
			attachment.onProgress(progressEvent);

			/* we expect the status to be 'ONGOING' and the value 0.5 (=500/1000) */
			expect(attachment.get('progress')).toBe(0.5);
			expect(attachment.get('uploadStatus')).toBe(1);
		});

		it('does not update its attributes if progress is not computable', function() {
			/* simulate a call to 'onProgress' with some example values */
			progressEvent = {
				lengthComputable: false,
				loaded: 1000,
				total: 1000
			}
			attachment.onProgress(progressEvent);

			/* we expect the status to be 'ONGOING' and the value 0.5 (=500/1000) */
			expect(attachment.get('progress')).toBe(0.5);
			expect(attachment.get('uploadStatus')).toBe(1);
		});
	});
});
