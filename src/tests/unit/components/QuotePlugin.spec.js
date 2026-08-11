/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Paragraph } from 'ckeditor5'
import QuotePlugin from '../../../ckeditor/quote/QuotePlugin.js'
import VirtualTestEditor from '../../virtualtesteditor.js'

describe('QuotePlugin', () => {
	it('Keep quote wrapper with QuotePlugin', async () => {
		const text = '<div class="quote"><p>bonjour bonjour</p></div>'
		const expected = '<div class="quote"><p>bonjour bonjour</p></div>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, QuotePlugin],
		})

		expect(editor.getData()).toEqual(expected)
	})

	it('Remove quote wrapper without QuotePlugin', async () => {
		const text = '<div class="quote"><p>bonjour bonjour</p></div>'
		const expected = '<p>bonjour bonjour</p>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph],
		})

		expect(editor.getData()).toEqual(expected)
	})

	it('Editor contains a <quote> element', async () => {
		const text = '<div class="quote"><p>bonjour bonjour</p></div>'

		const editor = await VirtualTestEditor.create({
			licenseKey: 'GPL',
			initialData: text,
			plugins: [Paragraph, QuotePlugin],
		})

		const range = editor.model.createRangeIn(editor.model.document.getRoot())

		let hasQuoteElement = false

		for (const value of range.getWalker({ shallow: true })) {
			if (value.item.is('element')) {
				if (value.item.name === 'quote') {
					hasQuoteElement = true
				}
			}
		}

		expect(hasQuoteElement).toBeTruthy()
	})
})
