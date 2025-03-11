/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import isString from 'lodash/fp/isString.js'
import { curry } from 'ramda'
import { convert } from 'html-to-text'

/**
 * @type {Text}
 */
export class Text {

	constructor(format, value) {
		this.format = format
		this.value = value
	}

	/**
	 * @param {Text} other other
	 * @return {Text}
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
	if (!isString(str)) {
		// Fall back to a hopefully sane default
		return plain('')
	}

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
 * @return bool
 */
export const isPlain = isFormat('plain')

/**
 * @function
 * @param {Text} text
 * @return bool
 */
export const isHtml = isFormat('html')

/**
 * @param {Text} text text
 * @return {Text}
 */
export const toPlain = (text) => {
	if (text.format === 'plain') {
		return text
	}

	// Build shared options for all block tags
	const blockTags = ['p', 'div', 'header', 'footer', 'form', 'article', 'aside', 'main', 'nav', 'section']
	const blockSelectors = blockTags.map(tag => ({
		selector: tag,
		format: 'customBlock',
		options: {
			preserveLeadingWhitespace: true,
		},
	}))

	const converted = convert(text.value, {
		wordwrap: false,
		formatters: {
			customBlock(elem, walk, builder, formatOptions) {
				builder.openBlock({
					isPre: formatOptions.preserveLeadingWhitespace,
					leadingLineBreaks: 0,
				})
				walk(elem.children, builder)
				builder.closeBlock({
					trailingLineBreaks: 0,
					blockTransform: text => text
						.replace(/^ {2,}/gm, ' '), // merge leading spaces
				})
				// Don't rely on the built-in leading/trailing line break feature.
				// Instead, we add a forced line break here because otherwise multiple
				// line breaks might be merged. But we want exactly one line break for
				// each closing tag.
				builder.addLineBreak()
			},
			customBlockQuote(elem, walk, builder, formatOptions) {
				builder.openBlock({
					leadingLineBreaks: formatOptions.leadingLineBreaks,
				})
				walk(elem.children, builder)
				builder.closeBlock({
					trailingLineBreaks: formatOptions.trailingLineBreaks,
					blockTransform: text => text
						.replace(/\n{3,}/g, '\n\n') // merge 3 or more line breaks
						.replace(/^/gm, '> '), // add quote marker at the start of each line
				})
			},
		},
		selectors: [
			{
				selector: 'img',
				format: 'skip',
			},
			{
				selector: 'a',
				options: {
					linkBrackets: false,
					ignoreHref: true,
				},
			},
			{
				selector: 'blockquote',
				format: 'customBlockQuote',
				options: {
					leadingLineBreaks: 0,
					trailingLineBreaks: 1,
				},
			},
			...blockSelectors,
		],
	})

	return plain(
		converted
			.replace(/^\n+/, '') // trim leading line breaks
			.replace(/\n+$/, '') // trim trailing line breaks
			.replace(/ +$/gm, '') // trim trailing spaces of each line
			.replace(/^--$/gm, '-- '), // hack to create the correct email signature separator
	)
}

/**
 * @param {Text} text text
 * @return {Text}
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
