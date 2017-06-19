/* global expect */

/**
 * @author Steffen Lindner <mail@steffen-lindner.de>
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


define(['views/errorview'], function(ErrorView) {

	describe('ErrorView', function () {

		var errorview;

		beforeEach(function () {
			errorview = new ErrorView({});
		});

		describe('Rendering', function () {

			it('produces the correct HTML', function () {
				errorview.render();

				var html = errorview.el.innerHTML.trim();
				var expected_html = '<div class="">\n\t<div class="icon-mail"></div>\n\t<h2>An unknown error occurred</h2>\n</div>';
				expect(html).toContain(expected_html);
			});
		});
	});
});
