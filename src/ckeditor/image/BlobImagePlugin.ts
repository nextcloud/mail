/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileLoader, UploadAdapter, UploadResponse, ViewDocumentFragment, ViewElement } from 'ckeditor5'

import { FileRepository, Plugin, UpcastWriter } from 'ckeditor5'
import logger from '../../logger.js'

const BASE64_DATA_URI = /^data:([^;,]*);base64,(.*)$/s

class BlobUploadAdapter implements UploadAdapter {
	private loader: FileLoader

	constructor(loader: FileLoader) {
		this.loader = loader
	}

	async upload(): Promise<UploadResponse> {
		const file = await this.loader.file
		return { default: URL.createObjectURL(file!) }
	}

	abort(): void {}
}

/**
 * Holds images as blob: object URLs while editing. Composer embeds them as
 * base64 again when the message is saved.
 */
export default class BlobImagePlugin extends Plugin {
	static get requires() {
		return [FileRepository] as const
	}

	static get pluginName() {
		return 'BlobImage' as const
	}

	init(): void {
		this.editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new BlobUploadAdapter(loader)

		this.editor.data.on('toModel', (event, [view]) => {
			const writer = new UpcastWriter((view as ViewElement | ViewDocumentFragment).document)

			for (const { item } of writer.createRangeIn(view as ViewElement | ViewDocumentFragment)) {
				if (!item.is('element', 'img')) {
					continue
				}

				const blobUrl = toBlobUrl(item.getAttribute('src') ?? '')
				if (blobUrl !== null) {
					writer.setAttribute('src', blobUrl, item)
				}
			}
		}, { priority: 'high' })
	}
}

/**
 * @param src image source to convert
 * @return an object URL for a base64 data URI, null for anything else
 */
function toBlobUrl(src: string): string | null {
	const match = BASE64_DATA_URI.exec(src)
	if (match === null) {
		return null
	}

	try {
		const bytes = Uint8Array.from(atob(match[2]), (char) => char.charCodeAt(0))
		return URL.createObjectURL(new Blob([bytes], { type: match[1] }))
	} catch (error) {
		logger.warn('Could not convert inline image to a blob, keeping base64', { error })
		return null
	}
}
