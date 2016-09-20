/**
 * @author Steffen Lindner <mail@steffen-lidnner.de>
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


define(['views/emptyfolderview', 'views/helper'], function(EmptyfolderView) {

	describe('EmptyfolderView', function () {

		var emptyfolderview;

		beforeEach(function () {
			emptyfolderview = new EmptyfolderView({});
		});

		describe('Rendering', function () {

			it('produces the correct HTML', function () {
				emptyfolderview.render();

				html = emptyfolderview.el.innerHTML.trim();
				expected_html = '<div class="icon-mail"></div>\n<h2>No messages in this folder!</h2>';
				expect(html).toContain(expected_html);

			});
		});
	});
});