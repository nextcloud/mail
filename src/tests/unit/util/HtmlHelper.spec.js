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

import {htmlToText} from '../../../util/HtmlHelper'

describe('HtmlHelper', () => {
	it('preserves breaks', () => {
		const html = 'line1<br>line2'
		const expected = 'line1\nline2'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('breaks on divs', () => {
		const html = '<div>one</div><div>two</div>'
		const expected = 'one\ntwo'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('produces a line break for each ending div element', () => {
		const html = '<div>' + '    <div>' + '        line1' + '    </div>' + '</div>' + '<div>line2</div>'
		const expected = ' line1\n\nline2'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('converts blocks to text', () => {
		const html = '<div>hello</div>'
		const expected = 'hello'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('converts paragraph to text', () => {
		const html = '<p>hello</p>'
		const expected = 'hello'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('converts paragraphs to text', () => {
		const html = '<p>hello</p><p>world</p>'
		const expected = 'hello\n\nworld'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('converts lists to text', () => {
		const html = '<ul><li>one</li><li>two</li><li>three</li></ul>'
		const expected = ' * one\n * two\n * three'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('converts deeply nested elements to text', () => {
		const html =
			'<html>' +
			'<body><p>Hello!</p><p>this <i>is</i> <b>some</b> random <strong>text</strong></p></body>' +
			'</html>'
		const expected = 'Hello!\n\nthis is some random text'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('does not leak internal redirection URLs', () => {
		const html = '<a href="https://localhost/apps/mail/redirect?src=domain.tld">domain.tld</a>'
		const expected = 'domain.tld'

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})

	it('preserves quotes', () => {
		const html = `<blockquote><div><b>yes.</b></div><div><br /></div><div>Am Montag, den 21.10.2019, 16:51 +0200 schrieb Christoph Wurst:</div><blockquote style="margin:0 0 0 .8ex;border-left:2px #729fcf solid;padding-left:1ex;"><div>ok cool</div><div><br /></div><div>Am Montag, den 21.10.2019, 16:51 +0200 schrieb Christoph Wurst:</div><blockquote style="margin:0 0 0 .8ex;border-left:2px #729fcf solid;padding-left:1ex;"><div>Hello</div><div><br /></div><div>this is some t<i>e</i>xt</div><div><br /></div><div>yes</div><div><br /></div><div>cheers</div><br></blockquote><br></blockquote></blockquote>`
		const expected = `> yes.
>
> Am Montag, den 21.10.2019, 16:51 +0200 schrieb Christoph Wurst:
> > ok cool
> >
> > Am Montag, den 21.10.2019, 16:51 +0200 schrieb Christoph Wurst:
> > > Hello
> > >
> > > this is some text
> > >
> > > yes
> > >
> > > cheers
> > >
> > >
> >`

		const actual = htmlToText(html)

		expect(actual).to.equal(expected)
	})
})
