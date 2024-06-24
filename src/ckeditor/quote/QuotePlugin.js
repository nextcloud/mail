/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin.js'

export default class Quote extends Plugin {

	init() {
		this._defineSchema()
		this._defineConverters()
	}

	_defineSchema() {
		const schema = this.editor.model.schema

		schema.register('quote', {
			inheritAllFrom: '$container',
		})
	}

	_defineConverters() {
		const conversion = this.editor.conversion

		conversion.elementToElement({
			model: 'quote',
			view: {
				name: 'div',
				classes: 'quote',
			},
		})
	}

}
