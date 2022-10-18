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

import VirtualTestEditor from '../../virtualtesteditor'
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph'
import SignaturePlugin from '../../../ckeditor/signature/SignaturePlugin'
import QuotePlugin from '../../../ckeditor/quote/QuotePlugin'

import {
	TRIGGER_CHANGE_ALIAS,
	TRIGGER_EDITOR_READY,
} from '../../../ckeditor/signature/InsertSignatureCommand'

describe('SignaturePlugin', () => {

	describe('TRIGGER_EDITOR_READY', () => {

		it('Add signature to content', async() => {
			const text = '<p>bonjour bonjour</p>'
			const expected = '<p>bonjour bonjour</p><div class="signature">--&nbsp;<p>&nbsp;</p><p>Jane Doe</p></div>'

			const editor = await VirtualTestEditor.create({
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
			const expected = '<p>bonjour bonjour</p><div class=\"signature\">--&nbsp;<p>&nbsp;</p><p>Jane Doe</p><p>&nbsp;</p></div><div class=\"quote\"><p>\"John Doe\" john.doe@localhost - January 1, 1970 1:00 AM</p><p>bonjour bonjour</p></div>'

			const editor = await VirtualTestEditor.create({
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
