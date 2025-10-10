/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin.js'
import { Paragraph } from '@ckeditor/ckeditor5-paragraph'

export default class Mail extends Plugin {

	static get requires() {
		return [Paragraph]
	}

	init() {
		this._overwriteParagraphConversion()
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

}
