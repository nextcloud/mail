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

export const TRIGGER_CHANGE_ALIAS = 'change_alias'
export const TRIGGER_EDITOR_READY = 'editor_ready'

export default class InsertSignatureCommand extends Command {

	/**
	 * Remove a signature element
	 *
	 * @param {module:core/editor/editor~Editor} editor instance
	 * @param {module:engine/model/writer~Writer} writer instance
	 */
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
	 * Insert a signature element
	 *
	 * @param {module:core/editor/editor~Editor} editor instance
	 * @param {module:engine/model/writer~Writer} writer instance
	 * @param {string} signature text
	 * @param {boolean} signatureAboveQuote signature position: above/below the quoted text
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

		const signaturePosition = this.findPosition(editor, writer, signatureAboveQuote)
		editor.model.insertContent(signatureElement, signaturePosition)
	}

	/**
	 * Find a position to insert the signature element.
	 *
	 * If signatureAboveQuote and a quote element exist the position
	 * above the quoted text otherwise the end of the document.
	 *
	 * @param {module:core/editor/editor~Editor} editor instance
	 * @param {module:engine/model/writer~Writer} writer instance
	 * @param {boolean} signatureAboveQuote signature position: above/below the quoted text
	 * @return {module:engine/model/position~Position} position instance
	 */
	findPosition(editor, writer, signatureAboveQuote) {
		if (signatureAboveQuote) {
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
		}

		return writer.createPositionAt(editor.model.document.getRoot(), 'end')
	}

	/**
	 * Check if a signature element exist
	 *
	 * @param {module:core/editor/editor~Editor} editor instance
	 * @return {boolean}
	 */
	hasSignatureElement(editor) {
		// Create a range spanning over the entire root content:
		const range = editor.model.createRangeIn(
			editor.model.document.getRoot()
		)

		// Iterate over all items in this range:
		for (const value of range.getWalker({ shallow: true })) {
			if (value.item.is('element')) {
				if (value.item.name === 'quote') {
					continue
				}
				if (value.item.name === 'signature') {
					return true
				}
			}
		}

		return false
	}

	/**
	 * @param {string} trigger TRIGGER_CHANGE_ALIAS or TRIGGER_EDITOR_READY
	 * @param {string} signature text
	 * @param {boolean} signatureAboveQuote signature position: above/below the quoted text
	 */
	execute(trigger, signature, signatureAboveQuote) {
		this.editor.model.change(writer => {
			/**
			 * TRIGGER_CHANGE_ALIAS:
			 * Current signature is replaced.
			 *
			 * Use case: User selects a different alias.
			 *
			 * TRIGGER_EDITOR_READY:
			 * Insert signature if non exist.
			 *
			 * Use case: Write new message, Modify signature, Save draft, Close Editor, Open editor
			 */

			if (trigger === TRIGGER_CHANGE_ALIAS) {
				this.removeSignatureElement(this.editor, writer)
				this.insertSignatureElement(this.editor, writer, signature, signatureAboveQuote)
			}

			if (trigger === TRIGGER_EDITOR_READY && !this.hasSignatureElement(this.editor)) {
				this.insertSignatureElement(this.editor, writer, signature, signatureAboveQuote)
			}
		})
	}

	refresh() {
		this.isEnabled = true
	}

}
