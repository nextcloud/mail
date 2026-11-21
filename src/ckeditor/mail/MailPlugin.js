/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ClipboardPipeline, Paragraph, Plugin, UpcastWriter } from 'ckeditor5'
import { normalizePastedLines, plainTextToLines, toPlainText } from './clipboard.js'

/**
 * Mail conventions for the composer: one paragraph is one line, like in
 * other mail clients, and blank lines are empty paragraphs.
 */
export default class Mail extends Plugin {
	static get requires() {
		return [Paragraph, ClipboardPipeline]
	}

	init() {
		this._overwriteParagraphConversion()
		this._pasteLinesAsTheyLook()
		this._copyLinesAsTheyLook()
	}

	/**
	 * Overwrite the elementToElement conversion
	 * from the default paragraph plugin to add
	 * margin:0 to every <p>.
	 *
	 * @private
	 */
	_overwriteParagraphConversion() {
		this.editor.conversion.elementToElement({
			model: 'paragraph',
			view: {
				name: 'p',
				styles: {
					margin: 0,
				},
			},
			converterPriority: 'high',
		})
	}

	/**
	 * CKEditor pastes plain text as one paragraph per blank line and HTML
	 * paragraphs as they are, which loses blank lines in the composer.
	 *
	 * @private
	 */
	_pasteLinesAsTheyLook() {
		const editor = this.editor
		const viewDocument = editor.editing.view.document

		this.listenTo(viewDocument, 'clipboardInput', (event, data) => {
			const text = data.dataTransfer.getData('text/plain')
			if (data.content || data.dataTransfer.getData('text/html') || !text.includes('\n')) {
				return
			}

			data.content = editor.data.htmlProcessor.toView(plainTextToLines(text))
		})

		this.listenTo(editor.plugins.get(ClipboardPipeline), 'inputTransformation', (event, data) => {
			if (!data.dataTransfer.getData('text/html')) {
				return
			}

			normalizePastedLines(
				data.content,
				new UpcastWriter(viewDocument),
				editor.data.htmlProcessor.domConverter.blockElements,
				data.dataTransfer.getData('text/plain'),
			)
		})
	}

	/**
	 * CKEditor puts a blank line between every two paragraphs in the plain
	 * text it copies. Replace it after CKEditor wrote it.
	 *
	 * @private
	 */
	_copyLinesAsTheyLook() {
		const viewDocument = this.editor.editing.view.document
		this.listenTo(viewDocument, 'clipboardOutput', (event, data) => {
			if (data.content.isEmpty) {
				return
			}

			data.dataTransfer.setData('text/plain', toPlainText(this.editor.data.htmlProcessor.domConverter, data.content))
		}, { priority: 'lowest' })
	}
}
