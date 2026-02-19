/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Command, first } from 'ckeditor5'

const ATTRIBUTE = 'textDirection'

/**
 * The text direction command. Applies `dir="ltr"` or `dir="rtl"` to selected blocks.
 */
export default class TextDirectionCommand extends Command {
	/**
	 * @inheritDoc
	 */
	refresh() {
		const firstBlock = first(this.editor.model.document.selection.getSelectedBlocks())

		this.isEnabled = Boolean(firstBlock) && this.editor.model.schema.checkAttribute(firstBlock, ATTRIBUTE)

		if (this.isEnabled && firstBlock.hasAttribute(ATTRIBUTE)) {
			this.value = firstBlock.getAttribute(ATTRIBUTE)
		} else {
			this.value = null
		}
	}

	/**
	 * Executes the command. Applies the text direction to the selected blocks.
	 *
	 * @param {object} options Command options.
	 * @param {string} options.value The direction value to apply ('ltr' or 'rtl').
	 */
	execute(options = {}) {
		const model = this.editor.model
		const doc = model.document
		const value = options.value

		model.change((writer) => {
			const blocks = Array.from(doc.selection.getSelectedBlocks())
				.filter((block) => this.editor.model.schema.checkAttribute(block, ATTRIBUTE))

			for (const block of blocks) {
				const currentDirection = block.getAttribute(ATTRIBUTE)

				// Toggle: if the same direction is applied, remove it
				if (currentDirection === value) {
					writer.removeAttribute(ATTRIBUTE, block)
				} else {
					writer.setAttribute(ATTRIBUTE, value, block)
				}
			}
		})
	}
}
