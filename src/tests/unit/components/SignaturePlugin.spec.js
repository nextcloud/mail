/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import VirtualTestEditor from '../../virtualtesteditor.js'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import SignaturePlugin from '../../../ckeditor/signature/SignaturePlugin.js'
import QuotePlugin from '../../../ckeditor/quote/QuotePlugin.js'

import {
	TRIGGER_CHANGE_ALIAS,
	TRIGGER_EDITOR_READY,
} from '../../../ckeditor/signature/InsertSignatureCommand.js'

describe('SignaturePlugin', () => {

	describe('TRIGGER_EDITOR_READY', () => {

		it('Add signature to content', async() => {
			const text = '<p>bonjour bonjour</p>'
			const expected = '<p>bonjour bonjour</p><div class="signature">--&nbsp;<p>&nbsp;</p><p>Jane Doe</p></div>'

			const editor = await VirtualTestEditor.create({
				licenseKey: 'GPL',
				initialData: text,
				plugins: [ParagraphPlugin, SignaturePlugin],
			})

			editor.execute('insertSignature',
				TRIGGER_EDITOR_READY,
				'<p>Jane Doe</p>',
				false,
			)

			expect(editor.getData()).toEqual(expected)
		})

		it('Keep existing signature', async() => {
			const text = '<p>bonjour bonjour</p><div class="signature"><p>--&nbsp;</p><p>Bob</p></div>'

			const editor = await VirtualTestEditor.create({
				licenseKey: 'GPL',
				initialData: text,
				plugins: [ParagraphPlugin, SignaturePlugin],
			})

			editor.execute('insertSignature',
				TRIGGER_EDITOR_READY,
				'<p>Jane Doe</p>',
				false,
			)

			expect(editor.getData()).toEqual(text)
		})

		it('Add signature to content above quote', async() => {
			const text = '<p>bonjour bonjour</p><div class="quote">"John Doe" john.doe@localhost - January 1, 1970 1:00 AM <blockquote><p>bonjour bonjour</p></blockquote></div>'
			const expected = '<p>bonjour bonjour</p><div class="signature">--&nbsp;<p>&nbsp;</p><p>Jane Doe</p><p>&nbsp;</p></div><div class=\"quote\"><p>\"John Doe\" john.doe@localhost - January 1, 1970 1:00 AM</p><p>bonjour bonjour</p></div>'

			const editor = await VirtualTestEditor.create({
				licenseKey: 'GPL',
				initialData: text,
				plugins: [ParagraphPlugin, QuotePlugin, SignaturePlugin],
			})

			editor.execute('insertSignature',
				TRIGGER_EDITOR_READY,
				'<p>Jane Doe</p>',
				true,
			)

			expect(editor.getData()).toEqual(expected)
		})

	})

	describe('TRIGGER_CHANGE_ALIAS', () => {

		it('Replace existing signature', async() => {
			const text = '<p>bonjour bonjour</p><div class="signature"><p>--&nbsp;</p><p>Bob</p></div>'
			const expected = '<p>bonjour bonjour</p><div class="signature">--&nbsp;<p>&nbsp;</p><p>Jane Doe</p></div>'

			const editor = await VirtualTestEditor.create({
				licenseKey: 'GPL',
				initialData: text,
				plugins: [ParagraphPlugin, SignaturePlugin],
			})

			editor.execute('insertSignature',
				TRIGGER_CHANGE_ALIAS,
				'<p>Jane Doe</p>',
				false,
			)

			expect(editor.getData()).toEqual(expected)
		})

	})

})
