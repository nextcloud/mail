/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const blobs = new Map()

/**
 * @param {Blob} blob image data
 * @return {string} object URL that embedBlobImages and embeddedSize can resolve
 */
export function createBlobUrl(blob) {
	const url = URL.createObjectURL(blob)
	blobs.set(url, blob)
	return url
}

/**
 * @param {string} html html with blob: image sources
 * @return {Promise<string>} html with those images as base64 data URIs
 */
export async function embedBlobImages(html) {
	if (!html.includes('blob:')) {
		return html
	}

	const doc = new DOMParser().parseFromString(html, 'text/html')
	const images = [...doc.querySelectorAll('img[src^="blob:"]')]
		.filter((img) => blobs.has(img.getAttribute('src')))

	await Promise.all(images.map(async (img) => {
		img.setAttribute('src', await readAsDataUri(blobs.get(img.getAttribute('src'))))
	}))

	return doc.body.innerHTML
}

/**
 * Size in bytes of the html once embedBlobImages has run, without encoding anything.
 *
 * @param {string} html html with blob: image sources
 * @return {number}
 */
export function embeddedSize(html) {
	let size = new Blob([html]).size

	for (const [url, blob] of blobs) {
		const occurrences = html.split(url).length - 1
		const dataUriLength = `data:${blob.type};base64,`.length + Math.ceil(blob.size / 3) * 4
		size += occurrences * (dataUriLength - url.length)
	}

	return size
}

/**
 * @param {Blob} blob image data
 * @return {Promise<string>}
 */
function readAsDataUri(blob) {
	return new Promise((resolve, reject) => {
		const reader = new FileReader()
		reader.onload = () => resolve(reader.result)
		reader.onerror = () => reject(reader.error)
		reader.readAsDataURL(blob)
	})
}
