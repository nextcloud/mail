/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createBlobUrl, embedBlobImages, embeddedSize } from '../../../util/blobImages.js'

describe('blobImages', () => {
	let counter = 0

	beforeEach(() => {
		URL.createObjectURL = vi.fn(() => `blob:http://localhost/${++counter}`)
	})

	afterEach(() => {
		delete URL.createObjectURL
	})

	it('leaves html without blob images untouched', async () => {
		const html = '<p>hello</p>'

		await expect(embedBlobImages(html)).resolves.toBe(html)
	})

	it('embeds blob images as base64', async () => {
		const url = createBlobUrl(new Blob(['image'], { type: 'image/png' }))

		const result = await embedBlobImages(`<p><img src="${url}" alt="logo"></p>`)

		expect(result).toBe('<p><img src="data:image/png;base64,aW1hZ2U=" alt="logo"></p>')
	})

	it('keeps unknown blob URLs', async () => {
		const html = '<p><img src="blob:http://localhost/unknown"></p>'

		await expect(embedBlobImages(html)).resolves.toBe(html)
	})

	it('measures the size after embedding', async () => {
		const url = createBlobUrl(new Blob(['image'], { type: 'image/png' }))
		const html = `<p><img src="${url}"></p><p><img src="${url}"></p>`

		expect(embeddedSize(html)).toBe(new Blob([await embedBlobImages(html)]).size)
	})

	it('measures html without blob images as it is', () => {
		const html = '<p>hello</p>'

		expect(embeddedSize(html)).toBe(html.length)
	})
})
