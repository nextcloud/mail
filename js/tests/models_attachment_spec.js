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

define([
	'models/attachment'
], function(Attachment) {
	describe('Attachment', function() {
		var attachment;

		it('has an random id', function() {
			var a1 = new Attachment({
				fileName: '/file1.png'
			});
			var a2 = new Attachment({
				fileName: '/cat.jpg'
			});

			expect(a1.get('id')).toBeDefined();
			expect(a2.get('id')).toBeDefined();

			expect(a1.get('id')).not.toBe(a2.get('id'));
		});

		it('ha no displayName if fileName is not set', function() {
			attachment = new Attachment();

			expect(attachment.get('displayName')).toBeUndefined();
		});

		it('removes leading slash from display name', function() {
			attachment = new Attachment({
				fileName: '/my/file.jpg'
			});

			expect(attachment.get('displayName')).toBe('my/file.jpg');
		});
	});
});
