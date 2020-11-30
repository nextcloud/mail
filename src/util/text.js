/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { curry } from 'ramda'
import { htmlToText } from 'html-to-text'

/**
 * @type {Text}
 */
class Text {

	constructor(format, value) {
		this.format = format
		this.value = value
	}

	/**
	 * @param {Text} other other
	 * @returns {Text}
	 */
	append(other) {
		if (this.format !== other.format) {
			throw new Error("can't append two different formats")
		}

		return new Text(this.format, this.value + other.value)
	}

}

/**
 * @param {string} format
 * @param {string} value
 *
 * @return {object}
 */
const wrap = curry((format, value) => {
	return new Text(format, value)
})

/**
 * @param {string} value
 * @return {Text}
 */
export const plain = wrap('plain')

/**
 * @function
 * @param {string} value
 * @return {Text}
 */
export const html = wrap('html')

export const detect = (str) => {
	if (!str.includes('>')) {
		return plain(str)
	} else {
		return html(str)
	}
}

/**
 * @function
 * @param {string} format
 * @param {Text} text
 */
const isFormat = curry((format, text) => {
	return text.format === format
})

/**
 * @function
 * @param {Text} text
 * @return {bool}
 */
export const isPlain = isFormat('plain')

/**
 * @function
 * @param {Text} text
 * @return {bool}
 */
export const isHtml = isFormat('html')

/**
 * @param {Text} text text
 * @returns {Text}
 */
export const toPlain = (text) => {
	if (text.format === 'plain') {
		return text
	}
	const withBlockBreaks = text.value.replace(/<\/div>/gi, '</div><br>')

	const converted = htmlToText(withBlockBreaks, {
		tags: {
			a: {
				options: {
					noLinkBrackets: true,
					ignoreHref: true,
				},
			},
			img: {
				format: 'skip',
			},
		},
		wordwrap: false,
		formatters: {
			blockquote(element, walk, builder, formatOptions) {
				walk(element.children, builder)
					.replace(/\n\n\n/g, '\n\n') // remove triple line breaks
					.replace(/^/gm, '> ') // add > quotation to each line
			},
			paragraph(element, walk, builder, formatOptions) {
				walk(element.children, builder)
				builder.addLineBreak()
				builder.addLineBreak()
			},
		},
	})

	return plain(
		converted
			.replace(/\n\n\n/g, '\n\n') // remove triple line breaks
			.replace(/^[\n\r]+/g, '') // trim line breaks at beginning and end
			.replace(/ $/gm, '') // trim white space at end of each line
	)
}

/**
 * @param {Text} text text
 * @returns {Text}
 */
export const toHtml = (text) => {
	if (text.format === 'html') {
		return text
	}
	if (text.format === 'plain') {
		return html(text.value.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2'))
	}

	throw new Error(`Unknown format ${text.format}`)
}
