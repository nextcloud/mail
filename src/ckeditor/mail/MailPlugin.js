/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
