/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

import {fromString} from 'html-to-text'

export const htmlToText = html => {
	const withBlockBreaks = html.replace(/<\/div>/gi, '</div><br>')

	const text = fromString(withBlockBreaks, {
		noLinkBrackets: true,
		ignoreHref: true,
		ignoreImage: true,
		wordwrap: false,
		format: {
			blockquote: function(element, fn, options) {
				return fn(element.children, options)
					.replace(/\n\n\n/g, '\n\n') // remove triple line breaks
					.replace(/^/gm, '> ') // add > quotation to each line
			},
			paragraph: function(element, fn, options) {
				return fn(element.children, options) + '\n'
			},
		},
	})

	return text
		.replace(/\n\n\n/g, '\n\n') // remove triple line breaks
		.replace(/^[\n\r]+/g, '') // trim line breaks at beginning and end
		.replace(/ $/gm, '') // trim white space at end of each line
}

export const textToSimpleHtml = text => {
	return text.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2')
}
