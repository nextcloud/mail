/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

import Command from '@ckeditor/ckeditor5-core/src/command.js'
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
			currentPosition,
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

				if (trigger === '@') {
					const mailtoHref = `mailto:${item.email}`
					const anchorText = `@${item.label}`
					const textElement = writer.createText(anchorText, { linkHref: mailtoHref })
					editor.model.insertContent(textElement)
				} else if (trigger === '!') {
					if (item.isHtml) {
						const viewFragment = editor.data.processor.toView(item.content)
						const modelFragment = editor.data.toModel(viewFragment)
						editor.model.insertContent(modelFragment)
					} else {
						const itemElement = writer.createElement('paragraph')
						writer.insertText(item.content, itemElement)
						editor.model.insertContent(itemElement)
					}
				} else {
					const itemElement = writer.createElement('paragraph')
					writer.insertText(item, itemElement)
					editor.model.insertContent(itemElement)
				}

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
