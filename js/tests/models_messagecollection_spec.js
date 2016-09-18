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

define(['models/messagecollection',
	'models/message'],
	function(MessageCollection, Message) {
		describe('MessageCollection', function() {
			var collection;

			beforeEach(function() {
				collection = new MessageCollection();
			});

			it('contains messages', function() {
				expect(collection.model).toBe(Message);
			});

			it('compares messages by date', function() {
				var message = new Message();
				message.set('dateInt', 12345);

				var cmp = collection.comparator(message);

				expect(cmp).toBe(-12345);
			});
		});
	});
