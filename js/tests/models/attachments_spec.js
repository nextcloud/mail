/* global expect */

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
	'models/attachments',
	'models/attachment'
], function(Attachents, Attachment) {
	describe('Attachments', function() {
		var attachments;

		beforeEach(function() {
			attachments = new Attachents();
		});

		it('contains attachments', function() {
			expect(attachments.model).toBe(Attachment);
		});
	});
});
