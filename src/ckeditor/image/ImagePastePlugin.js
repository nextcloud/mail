/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Plugin } from 'ckeditor5'
import logger from '../../logger.js'
import { fetchImageAsDataUri } from '../../service/ImageProxyService.js'

const EXTERNAL_SRC = /^https?:\/\//i

/**
 * Inlines images that are pasted from another web page. Such images keep their
 * original external `src`, which the editor's strict CSP refuses to load, so
 * they render as the broken/alt-text placeholder. Fetching them through the
 * server-side image proxy turns them into data: URIs that both display in the
 * editor and are sent as inline attachments — exactly like the "insert image
 * from URL" feature.
 */
export default class ImagePastePlugin extends Plugin {
	static get pluginName() {
		return 'ImagePastePlugin'
	}

	init() {
		const model = this.editor.model

		model.document.on('change:data', () => {
			const images = []
			for (const change of model.document.differ.getChanges()) {
				if (change.type !== 'insert') {
					continue
				}
				const node = change.position?.nodeAfter
				if (node) {
					this._collectExternalImages(node, images)
				}
			}

			images.forEach((image) => this._inlineExternalImage(image))
		})
	}

	/**
	 * Recursively collects images with an external src from an inserted node.
	 *
	 * @param {module:engine/model/node~Node} node the inserted node to inspect
	 * @param {Array} found accumulator of matching image elements
	 * @private
	 */
	_collectExternalImages(node, found) {
		if (!node.is?.('element')) {
			return
		}

		const isImage = node.is('element', 'imageBlock') || node.is('element', 'imageInline')
		if (isImage && EXTERNAL_SRC.test(node.getAttribute('src') ?? '')) {
			found.push(node)
		}

		for (const child of node.getChildren()) {
			this._collectExternalImages(child, found)
		}
	}

	/**
	 * Fetches an external image through the proxy and rewrites its src to the
	 * returned data: URI. Runs asynchronously, so the model is re-checked before
	 * the write in case the element was removed in the meantime.
	 *
	 * @param {module:engine/model/element~Element} image the image element
	 * @private
	 */
	async _inlineExternalImage(image) {
		const src = image.getAttribute('src')
		try {
			const dataUri = await fetchImageAsDataUri(src)
			this.editor.model.change((writer) => {
				if (image.root?.rootName && image.parent) {
					writer.setAttribute('src', dataUri, image)
				}
			})
		} catch (error) {
			logger.error('Could not inline pasted image', { error, src })
		}
	}
}
