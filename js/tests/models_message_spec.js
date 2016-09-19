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
	'models/message',
	'models/messageflags'
], function(Message, MessageFlags) {
	describe('Message', function() {
		var message;

		beforeEach(function() {
			message = new Message();
		});

		it('has flags and is inactive by default', function() {
			expect(message.get('flags') instanceof MessageFlags).toBe(true);
			expect(message.get('active')).toBe(false);
		});

		it('triggers a change event whenever flags change', function() {
			var hnd1 = jasmine.createSpy('handler1');
			var hnd2 = jasmine.createSpy('handler2');

			message.on('change', hnd1);
			message.on('change', hnd2);

			message.get('flags').trigger('change');

			expect(hnd1).toHaveBeenCalled();
			expect(hnd2).toHaveBeenCalled();
		});
	});
});
