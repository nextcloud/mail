/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { h } from 'vue'
import AttachmentMixin from '../../../mixins/AttachmentMixin.js'

const attachments = [
	{ downloadUrl: '/cat.png', fileName: 'cat.png', mime: 'image/png' },
	{ downloadUrl: '/notes.txt', fileName: 'notes.txt', mime: 'text/plain' },
	{ downloadUrl: '/invoice.pdf', fileName: 'invoice.pdf', mime: 'application/pdf' },
	{ downloadUrl: '/lower.pdf', fileName: 'lower.pdf', mime: 'application/octet-stream' },
	{ downloadUrl: '/upper.PDF', fileName: 'upper.PDF', mime: 'application/octet-stream' },
	{ downloadUrl: '/ordinary.xml', fileName: 'ordinary.xml', mime: 'application/octet-stream' },
	{ downloadUrl: '/other.pdf', fileName: 'other.pdf', mime: 'application/x-msdownload' },
	{ downloadUrl: '/unnamed', fileName: null, mime: 'application/octet-stream' },
]

function mountMixin() {
	return shallowMount({
		mixins: [AttachmentMixin],
		data: () => ({ attachments }),
		render: () => h('div'),
	})
}

describe('AttachmentMixin', () => {
	afterEach(() => {
		delete global.OCA
	})

	it('previews attachments supported by the viewer', () => {
		global.OCA = { Viewer: { mimetypes: ['image/png'], availableHandlers: [] } }

		const view = mountMixin()

		expect(view.vm.previewableFileInfos).toHaveLength(1)
		expect(view.vm.canPreview(view.vm.fileInfos[0])).toBe(true)
		expect(view.vm.canPreview(view.vm.fileInfos[1])).toBe(false)
	})

	it('maps generic PDF MIME by filename without modifying downloads or other MIME types', () => {
		const view = mountMixin()
		const fileInfos = view.vm.fileInfos

		expect(fileInfos.slice(3).map((fileInfo) => fileInfo.mime)).toEqual([
			'application/pdf',
			'application/pdf',
			'application/octet-stream',
			'application/x-msdownload',
			'application/octet-stream',
		])
		expect(fileInfos[3].filename).toBe('/lower.pdf')
		expect(fileInfos[3].source).toBe('/lower.pdf')
		expect(fileInfos[4].source).toBe('/upper.PDF')
		expect(view.vm.attachments[3].mime).toBe('application/octet-stream')
	})

	it('previews nothing when the viewer app is disabled', () => {
		global.OCA = {}

		const view = mountMixin()

		expect(view.vm.previewableFileInfos).toEqual([])
		expect(view.vm.canPreview(view.vm.fileInfos[0])).toBe(false)
	})

	it('does not preview pdf attachments without files_pdfviewer', () => {
		global.OCA = {
			Viewer: {
				mimetypes: ['application/pdf'],
				availableHandlers: [{ id: 'onlyoffice', mimes: ['application/pdf'] }],
			},
		}

		const view = mountMixin()

		expect(view.vm.previewableFileInfos).toEqual([])
		expect(view.vm.canPreview(view.vm.fileInfos[2])).toBe(false)
	})

	it('opens pdf attachments with files_pdfviewer', () => {
		const openWith = vi.fn()
		const open = vi.fn()
		global.OCA = {
			Viewer: {
				mimetypes: ['application/pdf', 'image/png'],
				availableHandlers: [{ id: 'onlyoffice' }, { id: 'pdf' }],
				openWith,
				open,
			},
		}

		const view = mountMixin()
		view.vm.showViewer(view.vm.fileInfos[2])

		expect(openWith).toHaveBeenCalledWith('pdf', {
			fileInfo: view.vm.fileInfos[2],
			list: view.vm.previewableFileInfos,
		})
		expect(open).not.toHaveBeenCalled()

		view.vm.showViewer(view.vm.fileInfos[0])

		expect(open).toHaveBeenCalledTimes(1)
	})
})
