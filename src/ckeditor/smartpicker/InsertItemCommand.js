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
export default class InsertItemCommand extends Command {

	/**
	 * @param {module:core/editor/editor~Editor} editor instance
	 * @param {module:engine/model/writer~Writer} writer instance
	 * @param {string} item smart picker or emoji picker
	 * @param {string} trigger the character to replace
	 */
	insertItem(editor, writer, item, trigger) {
		const currentPosition = editor.model.document.selection.getLastPosition()
		if (currentPosition === null) {
			// null as current position is probably not possible
			// @TODO Add error to handle such a situation in the callback
			return
		}

		const range = editor.model.createRange(
			currentPosition.getShiftedBy(-5),
			currentPosition
		)

		// Iterate over all items in this range:
		const walker = range.getWalker({ shallow: false, direction: 'backward' })

		for (const value of walker) {
			if (value.type === 'text' && value.item.data.includes(trigger)) {
				writer.remove(value.item)

				const text = value.item.data
				const lastSlash = text.lastIndexOf(trigger)

				const textElement = writer.createElement('paragraph')
				writer.insertText(text.substring(0, lastSlash), textElement)
				editor.model.insertContent(textElement)

				const itemElement = writer.createElement('paragraph')
				writer.insertText(item, itemElement)
				editor.model.insertContent(itemElement)

				return
			}
		}

		// @TODO If we end up here, we did not find the slash. We should throw an error maybe.
	}

	/**
	 * @param {string}  item link from smart picker or emoji from emoji picker
	 * @param {string} trigger the character to replace
	 */
	execute(item, trigger) {
		this.editor.model.change(writer => {
			this.insertItem(this.editor, writer, item, trigger)
		})
	}

	refresh() {
		this.isEnabled = true
	}

}
