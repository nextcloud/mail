/*
 * @copyright 2022 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author 2022 Daniel Kesselberg <mail@danielkesselberg.de>
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
 */

import VirtualTestEditor from '../../virtualtesteditor.js'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import QuotePlugin from '../../../ckeditor/quote/QuotePlugin.js'

describe('QuotePlugin', () => {

	it('Keep quote wrapper with QuotePlugin', async() => {
		const text = '<div class="quote"><p>bonjour bonjour</p></div>'
		const expected = '<div class=\"quote\"><p>bonjour bonjour</p></div>'

		const editor = await VirtualTestEditor.create({
			initialData: text,
			plugins: [ParagraphPlugin, QuotePlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

	it('Remove quote wrapper without QuotePlugin', async() => {
		const text = '<div class="quote"><p>bonjour bonjour</p></div>'
		const expected = '<p>bonjour bonjour</p>'

		const editor = await VirtualTestEditor.create({
			initialData: text,
			plugins: [ParagraphPlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})


	it('Editor contains a <quote> element', async() => {
		const text = '<div class="quote"><p>bonjour bonjour</p></div>'

		const editor = await VirtualTestEditor.create({
			initialData: text,
			plugins: [ParagraphPlugin, QuotePlugin],
		})

		const range = editor.model.createRangeIn(
			editor.model.document.getRoot()
		)

		let hasQuoteElement = false;

		for (const value of range.getWalker({ shallow: true })) {
			if (value.item.is('element')) {
				if (value.item.name === 'quote') {
					hasQuoteElement = true;
				}
			}
		}

		expect(hasQuoteElement).toBeTruthy()
	})

})
