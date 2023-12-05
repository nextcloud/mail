/**
 * @copyright 2021 Silvio Zimmer <silvio.zimmer@die-kinderwelt.com>
 *
 * @author 2021 Silvio Zimmer <silvio.zimmer@die-kinderwelt.com>
 *
 * @license AGPL-3.0-or-later
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

import Plugin from '@ckeditor/ckeditor5-core/src/plugin'

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
