/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ClassicEditor, ImageBlock, ImageInline, ImageResizeEditing, ImageStyleEditing, Paragraph } from 'ckeditor5'
import ImageDowncastPlugin from '../../../../ckeditor/image/ImageDowncastPlugin.ts'

// The editor UI observes the size of its toolbar, which jsdom does not provide.
window.ResizeObserver = class {
	observe() {}
	unobserve() {}
	disconnect() {}
}

/**
 * Get the data of an editor initialised with the given content.
 *
 * @param {string} initialData content to load into the editor
 * @return {Promise<string>} the editor's data output
 */
async function downcast(initialData) {
	const element = document.createElement('div')
	document.body.appendChild(element)

	const editor = await ClassicEditor.create(element, {
		licenseKey: 'GPL',
		initialData,
		plugins: [Paragraph, ImageBlock, ImageInline, ImageResizeEditing, ImageStyleEditing, ImageDowncastPlugin],
		image: {
			resizeUnit: 'px',
			styles: {
				options: ['alignBlockLeft', 'alignCenter', 'alignBlockRight'],
			},
		},
	})

	const data = editor.data.get()
	await editor.destroy()
	element.remove()

	return data
}

/**
 * A 400x300 image resized to the given CSS width.
 *
 * @param {string} width the CSS width on the figure
 * @return {string} the figure markup
 */
function resized(width) {
	return `<figure class="image image_resized" style="width:${width};">`
		+ '<img src="test.png" width="400" height="300">'
		+ '</figure>'
}

describe('ImageDowncastPlugin', () => {
	it('mirrors a resized width onto the width attribute', async () => {
		const data = await downcast(resized('200px'))

		expect(data).toContain('width="200"')
		expect(data).toContain('width:200px;')
	})

	it('drops the natural height so the image is not stretched', async () => {
		const data = await downcast(resized('200px'))

		expect(data).not.toContain('height=')
	})

	it('keeps the aspect ratio the editor writes', async () => {
		const data = await downcast(resized('200px'))

		expect(data).toContain('aspect-ratio:400/300;')
	})

	it('keeps the attributes of an image resized to a percentage', async () => {
		const data = await downcast(resized('50%'))

		expect(data).toContain('width="400"')
		expect(data).toContain('height="300"')
	})

	it('keeps the attributes of an image that was not resized', async () => {
		const data = await downcast('<figure class="image"><img src="test.png" width="400" height="300"></figure>')

		expect(data).toContain('width="400"')
		expect(data).toContain('height="300"')
	})

	it('mirrors the width of a linked image', async () => {
		const data = await downcast('<figure class="image image_resized" style="width:100px;">'
			+ '<a href="https://nextcloud.com"><img src="test.png" width="400" height="300"></a>'
			+ '</figure>')

		expect(data).toContain('width="100"')
		expect(data).not.toContain('height=')
	})

	it('mirrors the width of a resized inline image', async () => {
		const data = await downcast('<p>text <img class="image_resized" style="width:200px;" src="test.png" width="400" height="300"></p>')

		expect(data).toContain('width="200"')
		expect(data).not.toContain('height=')
	})

	it('keeps the width when the message is reopened and sent again', async () => {
		const sent = await downcast(resized('200px'))
		const resent = await downcast(sent)

		expect(resent).toContain('width="200"')
		expect(resent).toContain('width:200px;')
		expect(resent).not.toContain('height=')
	})

	it('inlines the styles of a centred image', async () => {
		const data = await downcast('<figure class="image image-style-align-center"><img src="test.png"></figure>')

		expect(data).toContain('margin-left:auto;')
		expect(data).toContain('margin-right:auto;')
		expect(data).toContain('text-align:center;')
		expect(data).toContain('image-style-align-center')
	})

	it('inlines the styles of a right aligned image', async () => {
		const data = await downcast('<figure class="image image-style-block-align-right"><img src="test.png"></figure>')

		expect(data).toContain('margin-left:auto;')
		expect(data).toContain('margin-right:0;')
		expect(data).toContain('text-align:right;')
	})

	it('left aligns an image without a style class', async () => {
		const data = await downcast('<figure class="image"><img src="test.png"></figure>')

		expect(data).toContain('margin-left:0;')
		expect(data).toContain('margin-right:auto;')
		expect(data).toContain('text-align:left;')
	})

	it('keeps the alignment when the message is reopened and sent again', async () => {
		const sent = await downcast('<figure class="image image-style-align-center"><img src="test.png"></figure>')
		const resent = await downcast(sent)

		expect(resent).toContain('text-align:center;')
	})
})
