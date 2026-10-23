/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ClassicEditor, ImageBlock, ImageInline, Paragraph } from 'ckeditor5'
import BlobImagePlugin from '../../../../ckeditor/image/BlobImagePlugin.ts'

window.ResizeObserver = class {
	observe() {}
	unobserve() {}
	disconnect() {}
}

/**
 * @param {string} initialData content to load into the editor
 * @return {Promise<ClassicEditor>}
 */
async function createEditor(initialData = '') {
	const element = document.createElement('div')
	document.body.appendChild(element)

	return ClassicEditor.create(element, {
		licenseKey: 'GPL',
		initialData,
		plugins: [Paragraph, ImageBlock, ImageInline, BlobImagePlugin],
	})
}

describe('BlobImagePlugin', () => {
	let editor

	beforeEach(() => {
		URL.createObjectURL = vi.fn(() => 'blob:http://localhost/image')
	})

	afterEach(async () => {
		await editor?.destroy()
		delete URL.createObjectURL
	})

	it('turns base64 images into blob URLs', async () => {
		editor = await createEditor('<p><img src="data:image/png;base64,aW1hZ2U="></p>')

		expect(editor.data.get()).toContain('src="blob:http://localhost/image"')
		const blob = URL.createObjectURL.mock.calls[0][0]
		expect(blob.type).toBe('image/png')
		expect(await blob.text()).toBe('image')
	})

	it('turns base64 images inserted as a view fragment into blob URLs', async () => {
		editor = await createEditor()

		const viewFragment = editor.data.processor.toView('<p><img src="data:image/png;base64,aW1hZ2U="></p>')
		editor.model.change((writer) => {
			writer.append(editor.data.toModel(viewFragment), editor.model.document.getRoot())
		})

		expect(editor.data.get()).toContain('src="blob:http://localhost/image"')
	})

	it('keeps other image sources', async () => {
		editor = await createEditor('<p><img src="https://example.com/image.png"></p>')

		expect(editor.data.get()).toContain('src="https://example.com/image.png"')
		expect(URL.createObjectURL).not.toHaveBeenCalled()
	})

	it('keeps invalid base64 as it is', async () => {
		editor = await createEditor('<p><img src="data:image/png;base64,***"></p>')

		expect(editor.data.get()).toContain('src="data:image/png;base64,***"')
		expect(URL.createObjectURL).not.toHaveBeenCalled()
	})

	it('uploads files as blob URLs', async () => {
		editor = await createEditor()
		const file = new File(['image'], 'image.png', { type: 'image/png' })

		const adapter = editor.plugins.get('FileRepository').createUploadAdapter({ file: Promise.resolve(file) })

		await expect(adapter.upload()).resolves.toEqual({ default: 'blob:http://localhost/image' })
		expect(URL.createObjectURL).toHaveBeenCalledWith(file)
	})
})
