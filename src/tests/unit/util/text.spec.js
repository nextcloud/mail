/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
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

import { detect, html, plain, toPlain } from '../../../util/text'

describe('text', () => {
	describe('toPlain', () => {
		it('preserves breaks', () => {
			const source = html('line1<br>line2')
			const expected = plain('line1\nline2')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('removes leading line breaks', () => {
			const source = html('<br><br><br>hello world')
			const expected = plain('hello world')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('removes trailing line breaks', () => {
			const source = html('hello world<br><br><br>')
			const expected = plain('hello world')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('removes trailing spaces of each line', () => {
			const source = html('line1   <br>line2 <br>line3')
			const expected = plain('line1\nline2\nline3')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('breaks on divs', () => {
			const source = html('<div>one</div><div>two</div>')

			const actual = toPlain(source)

			expect(actual).toEqual(plain('one\ntwo'))
		})

		it('merges spaces at the beginning of a line', () => {
			const source = html('<div>   <div>  line1</div></div>')
			const expected = plain(' line1')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('produces a line break for each ending div element', () => {
			const source = html('<div><div>line1</div></div><div>line3</div>')
			const expected = plain('line1\n\nline3')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('converts blocks to text', () => {
			const source = html('<div>hello</div>')
			const expected = plain('hello')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('converts paragraph to text', () => {
			const source = html('<p>hello</p>')
			const expected = plain('hello')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('produces a single line break between paragraphs', () => {
			const source = html('<p>hello</p><p>world</p>')
			const expected = plain('hello\nworld')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('produces a single line break between a div and a paragraph', () => {
			const source = html('<div>hello</div><p>world</p>')
			const expected = plain('hello\nworld')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('produces a single line break after each block element', () => {
			const selectors = ['p', 'div', 'header', 'footer', 'form', 'article', 'aside', 'main', 'nav', 'section']
			const source = html(
				selectors
					.map(tag => `<${tag}>foobar</${tag}>`)
					.join('')
			)
			const expected = plain(selectors.map(tag => 'foobar').join('\n'))

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('produces exactly one line break for each closing block element', () => {
			const selectors = ['p', 'div', 'header', 'footer', 'form', 'article', 'aside', 'main', 'nav', 'section']
			const source = html(
				selectors
					.map(tag => `<${tag}><${tag}>foobar</${tag}></${tag}>`)
					.join('')
			)
			const expected = plain(selectors.map(tag => 'foobar').join('\n\n'))

			const actual = toPlain(source)


			expect(actual).toEqual(expected)
		})

		it('converts lists to text', () => {
			const source = html('<ul><li>one</li><li>two</li><li>three</li></ul>')
			const expected = plain(' * one\n * two\n * three')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('converts deeply nested elements to text', () => {
			const source = html(
				'<html>'
					+ '<body><p>Hello!</p><p>this <i>is</i> <b>some</b> random <strong>text</strong></p></body>'
					+ '</html>'
			)
			const expected = plain('Hello!\nthis is some random text')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('does not leak internal redirection URLs', () => {
			const source = html('<a href="https://localhost/apps/mail/redirect?src=domain.tld">domain.tld</a>')
			const expected = plain('domain.tld')

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})

		it('preserves quotes', () => {
			const source = html(
				'<blockquote><div><b>yes.</b></div><div><br /></div><div>Am Montag, den 21.10.2019, 16:51 +0200 schrieb Christoph Wurst:</div><blockquote style="margin:0 0 0 .8ex;border-left:2px #729fcf solid;padding-left:1ex;"><div>ok cool</div><div><br /></div><div>Am Montag, den 21.10.2019, 16:51 +0200 schrieb Christoph Wurst:</div><blockquote style="margin:0 0 0 .8ex;border-left:2px #729fcf solid;padding-left:1ex;"><div>Hello</div><div><br /></div><div>this is some t<i>e</i>xt</div><div><br /></div><div>yes</div><div><br /></div><div>cheers</div><br></blockquote><br></blockquote></blockquote>'
			)
			const expected = plain(`> yes.
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
> >`)

			const actual = toPlain(source)

			expect(actual).toEqual(expected)
		})
	})

	describe('detect', () => {
		it('detects plain text', () => {
			const text = 'hello world\nsecond line'

			const detected = detect(text)

			expect(detected).toEqual(plain(text))
		})

		it('detects html', () => {
			const text = '<p>hello world</p><p>second line</p>'

			const detected = detect(text)

			expect(detected).toEqual(html(text))
		})
	})
})
