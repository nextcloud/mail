/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin.js'
import InsertSignatureCommand from './InsertSignatureCommand.js'

export default class Signature extends Plugin {

	init() {
		this._defineSchema()
		this._defineConverters()

		this.editor.commands.add(
			'insertSignature',
			new InsertSignatureCommand(this.editor),
		)
	}

	_defineSchema() {
		const schema = this.editor.model.schema

		schema.register('signature', {
			inheritAllFrom: '$container',
		})
	}

	_defineConverters() {
		const conversion = this.editor.conversion

		conversion.elementToElement({
			model: 'signature',
			view: {
				name: 'div',
				classes: 'signature',
			},
		})
	}

}
