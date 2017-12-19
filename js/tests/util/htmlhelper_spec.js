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

		it('converts divs to newlines', function() {
			var html = '<div>line1</div><div>line2</div>';
			var expected = 'line1\nline2';

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
			var expected = 'line1\nline2';

			var actual = HtmlHelper.htmlToText(html);

			expect(actual).toBe(expected);
		});

		it('converts the HTML correctly', function() {
			expect(HtmlHelper.htmlToText('<div>hello</div>')).toEqual('hello');
			expect(HtmlHelper.htmlToText('<div>hello</div><div>World</div>'))
					.toEqual('hello\nWorld');

			expect(HtmlHelper.htmlToText('<p>hello</p>'))
					.toEqual('hello');

			expect(HtmlHelper.htmlToText('<ul><li>one</li><li>two</li><li>three</li></ul>'))
					.toEqual('one\ntwo\nthree');

			expect(HtmlHelper.htmlToText('<html>'
					+ '<body><p>Hello!</p><p>this <i>is</i> <b>some</b> random <strong>text</strong></p></body>'
					+ '</html>'))
					.toEqual('Hello!\nthis is some random text');
		});
	});
});
