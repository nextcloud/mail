/* global expect */

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define([
	'util/htmlhelper'
], function(HtmlHelper) {
	'use strict';

	describe('HtmlHelper', function() {
		it('preserves breaks', function() {
			var html = 'line1<br>line2';
			var expected = 'line1\nline2';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('concats divs', function() {
			var html = '<div>one</div><div>two</div>';
			var expected = 'onetwo';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('does not produce large number of line breaks for nested elements', function() {
			var html =
					'<div>' +
					'    <idv>' +
					'        line1' +
					'    </div>' +
					'</div>' +
					'<div>line2</div>';
			var expected = 'line1 line2';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('converts blocks to text', function() {
			var html = '<div>hello</div>';
			var expected = 'hello';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('converts paragraphs to text', function() {
			var html = '<p>hello</p>';
			var expected = 'hello';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('converts lists to text', function() {
			var html = '<ul><li>one</li><li>two</li><li>three</li></ul>';
			var expected = '* one\n * two\n * three';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('converts deeply nested elements to text', function() {
			var html = '<html>'
					+ '<body><p>Hello!</p><p>this <i>is</i> <b>some</b> random <strong>text</strong></p></body>'
					+ '</html>';
			var expected = 'Hello!\n\nthis is some random text';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('does not leak internal redirection URLs', function() {
			var html = '<a href="https://localhost/apps/mail/redirect?src=domain.tld">domain.tld</a>';
			var expected = 'domain.tld';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});
	});
});
