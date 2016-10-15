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
	'models/folder',
	'models/messagecollection',
	'models/message'
], function(Folder, MessageCollection, Message) {
	describe('Folder', function() {
		var folder;

		beforeEach(function() {
			folder = new Folder();
		});

		it('has messages', function() {
			expect(folder.messages instanceof MessageCollection).toBe(true);
		});

		it('toggles open', function() {
			expect(folder.get('open')).toBe(false);

			folder.toggleOpen();

			expect(folder.get('open')).toBe(true);

			folder.toggleOpen();

			expect(folder.get('open')).toBe(false);
		});

		it('assigns itself to added messages', function() {
			var message = new Message();

			expect(folder.messages.length).toBe(0);

			folder.addMessage(message);

			expect(folder.messages.length).toBe(1);
			expect(message.folder).toBe(folder);
		});
	});
});
