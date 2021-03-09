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

	insertSignatureElement(editor, writer, value) {
		// Skip empty signature
		if (value.length === 0) {
			return
		}

		// Convert an HTML string to a view document fragment:
		const viewFragment = editor.data.processor.toView(value)

		// Convert the view document fragment to a model document fragment
		// in the context of $root. This conversion takes the schema into
		// account so if, for example, the view document fragment contained a bare text node,
		// this text node cannot be a child of $root, so it will be automatically
		// wrapped with a <paragraph>. You can define the context yourself (in the second parameter),
		// and e.g. convert the content like it would happen in a <paragraph>.
		// Note: The clipboard feature uses a custom context called $clipboardHolder
		// which has a loosened schema.
		const modelFragment = editor.data.toModel(viewFragment)

		const signature = writer.createElement('signature')
		writer.append(writer.createText('--'), signature)
		writer.append(writer.createElement('paragraph'), signature)
		writer.append(modelFragment, signature)

		editor.model.insertContent(
			signature,
			writer.createPositionAt(editor.model.document.getRoot(), 'end')
		)
	}

	/**
	 * @param {string} value signature to append
	 */
	execute({ value }) {
		this.editor.model.change(writer => {
			this.removeSignatureElement(this.editor, writer)
			this.insertSignatureElement(this.editor, writer, value)
		})
	}

	refresh() {
		this.isEnabled = true
	}

}
