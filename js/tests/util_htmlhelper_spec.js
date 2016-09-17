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

define(['util/htmlhelper'], function(helper) {
	describe('HTMLHelper test', function() {
		describe('Simple HTML', function() {
			it('converts the HTML correctly', function() {
				expect(helper.htmlToText('<div>hello</div>')).toEqual('hello');
				expect(helper.htmlToText('<div>hello</div><div>World</div>'))
					.toEqual('hello\nWorld');

				expect(helper.htmlToText('<p>hello</p>'))
					.toEqual('hello');

				expect(helper.htmlToText('<ul><li>one</li><li>two</li><li>three</li></ul>'))
					.toEqual('one\ntwo\nthree');

				expect(helper.htmlToText('<html>'
				+ '<body><p>Hello!</p><p>this <i>is</i> <b>some</b> random <strong>text</strong></p></body>'
				+ '</html>'))
					.toEqual('Hello!\nthis is some random text');
			});
		});
	});
});
