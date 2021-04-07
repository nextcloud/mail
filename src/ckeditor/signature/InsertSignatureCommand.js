/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

import Command from '@ckeditor/ckeditor5-core/src/command'

export default class InsertSignatureCommand extends Command {

	removeSignatureElement(editor, writer) {
		// Create a range spanning over the entire root content:
		const range = editor.model.createRangeIn(
			editor.model.document.getRoot()
		)

		// Iterate over all items in this range:
		for (const value of range.getWalker({ shallow: true })) {
			if (value.item.is('element') && value.item.name === 'signature') {
				writer.remove(value.item)
			}
		}
	}

	/**
	 *
	 * @param {*} editor the editor instance
	 * @param {*} writer the writer instance
	 * @param {string} signature the signature text
	 * @param {boolean} signatureAboveQuote the signature position: above/below the quoted text
	 */
	insertSignatureElement(editor, writer, signature, signatureAboveQuote) {
		// Skip empty signature
		if (signature.length === 0) {
			return
		}

		// Convert an HTML string to a view document fragment:
		const viewFragment = editor.data.processor.toView(signature)

		// Convert the view document fragment to a model document fragment
		// in the context of $root. This conversion takes the schema into
		// account so if, for example, the view document fragment contained a bare text node,
		// this text node cannot be a child of $root, so it will be automatically
		// wrapped with a <paragraph>. You can define the context yourself (in the second parameter),
		// and e.g. convert the content like it would happen in a <paragraph>.
		// Note: The clipboard feature uses a custom context called $clipboardHolder
		// which has a loosened schema.
		const modelFragment = editor.data.toModel(viewFragment)

		const signatureElement = writer.createElement('signature')
		writer.append(writer.createText('-- '), signatureElement)
		writer.append(writer.createElement('paragraph'), signatureElement)
		writer.append(modelFragment, signatureElement)
		if (signatureAboveQuote) {
			writer.append(writer.createElement('paragraph'), signatureElement)
		}

		const signaturePosition = signatureAboveQuote ? this.findPositionAboveQuote(editor, writer) : writer.createPositionAt(editor.model.document.getRoot(), 'end')
		editor.model.insertContent(signatureElement, signaturePosition)
	}

	/**
	 *
	 * @param {*} editor the editor instance
	 * @param {*} writer the writer instance
	 * @returns {*} the position above the quoted text; position 1 if no quote found
	 */
	findPositionAboveQuote(editor, writer) {
		// Create a range spanning over the entire root content:
		const range = editor.model.createRangeIn(
			editor.model.document.getRoot()
		)

		// Iterate over all items in this range:
		for (const value of range.getWalker({ shallow: true })) {
			if (value.item.is('element') && value.item.name === 'quote') {
			 return writer.createPositionBefore(value.item)
			}
		}

		return writer.createPositionAt(editor.model.document.getRoot(), 1)
	}

	/**
	 *
	 * @param {*} param0 the signature text and position
	 */
	execute({ signature, signatureAboveQuote }) {
		this.editor.model.change(writer => {
			this.removeSignatureElement(this.editor, writer)
			this.insertSignatureElement(this.editor, writer, signature, signatureAboveQuote)
		})
	}

	refresh() {
		this.isEnabled = true
	}

}
