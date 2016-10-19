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


define(['models/alias'], function(Alias) {
	describe('Alias test', function() {
		var alias;

		beforeEach(function() {
			alias = new Alias();
		});

		it('serializes the alias model', function() {
			alias.set('id', 123);
			alias.set('alias', 'alias@example.com');

			var serialized = alias.toJSON();

			expect(serialized).toEqual({
				id: 123,
				alias: 'alias@example.com'
			});
		});
	});
});
