/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ViewDocumentFragment, ViewElement } from 'ckeditor5'

import { ImageUtils, Plugin, UpcastWriter } from 'ckeditor5'

const ALIGNMENTS: Record<string, Record<string, string>> = {
	'image-style-align-center': { 'margin-left': 'auto', 'margin-right': 'auto', 'text-align': 'center' },
	'image-style-block-align-right': { 'margin-left': 'auto', 'margin-right': '0', 'text-align': 'right' },
	'image-style-block-align-left': { 'margin-left': '0', 'margin-right': 'auto', 'text-align': 'left' },
}

/**
 * Parse a CSS length into whole pixels. Anything but an absolute pixel value
 * yields null.
 *
 * @param value the CSS length to parse
 */
function toPixels(value: string | undefined): number | null {
	if (value === undefined) {
		return null
	}

	const pixels = /^\s*([\d.]+)\s*px\s*$/.exec(value)
	if (pixels === null) {
		return null
	}

	const width = Math.round(Number.parseFloat(pixels[1]))

	return width > 0 ? width : null
}

/**
 * Sizes images in the editor's data output only; the editing view keeps
 * CKEditor's own markup.
 */
export default class ImageDowncastPlugin extends Plugin {
	static get requires() {
		return [ImageUtils] as const
	}

	static get pluginName() {
		return 'ImageDowncast' as const
	}

	init(): void {
		// The width and the natural size are written by two separate converters,
		// so post-process the finished view instead of overriding either.
		this.editor.data.on('toView', (event) => {
			const fragment = event.return as ViewDocumentFragment
			const writer = new UpcastWriter(fragment.document)

			for (const { item } of writer.createRangeIn(fragment)) {
				const isImageFigure = item.is('element', 'figure') && item.hasClass('image')
				if (isImageFigure) {
					this._inlineAlignment(writer, item)
				}

				// A block image carries the resized width on its figure, an inline
				// one on the img itself.
				if (isImageFigure || item.is('element', 'img')) {
					this._mirrorResizedWidth(writer, item)
				}
			}
		}, { priority: 'low' })
	}

	/**
	 * Inlines the styles of the figure's alignment class. The class must stay,
	 * reopening the draft reads the alignment from it.
	 *
	 * @param writer view writer of the data view
	 * @param figure the figure to align
	 */
	_inlineAlignment(writer: UpcastWriter, figure: ViewElement): void {
		const className = Object.keys(ALIGNMENTS).find((candidate) => figure.hasClass(candidate))
			?? 'image-style-block-align-left'
		const alignment = ALIGNMENTS[className]

		writer.setStyle(alignment, figure)

		const image = this.editor.plugins.get('ImageUtils').findViewImgElement(figure)
		if (image === undefined) {
			return
		}

		// For clients that drop <figure>; auto margins only move a block element.
		writer.setStyle({
			display: 'block',
			'margin-left': alignment['margin-left'],
			'margin-right': alignment['margin-right'],
		}, image)
	}

	/**
	 * Mirrors a resized image's CSS width onto the img width attribute, which
	 * clients that drop CSS still honour. Reopening the message reads that width
	 * back as the image's natural size.
	 *
	 * @param writer view writer of the data view
	 * @param element the figure or img holding the resized width
	 */
	_mirrorResizedWidth(writer: UpcastWriter, element: ViewElement): void {
		const resizedWidth = toPixels(element.getStyle('width'))
		if (resizedWidth === null) {
			return
		}

		const image = this.editor.plugins.get('ImageUtils').findViewImgElement(element)
		if (image === undefined) {
			return
		}

		writer.setAttribute('width', String(resizedWidth), image)
		// A natural height next to the smaller width would stretch the image.
		writer.removeAttribute('height', image)
	}
}
