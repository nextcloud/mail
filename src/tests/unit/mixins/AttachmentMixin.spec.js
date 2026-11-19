/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { h } from 'vue'
import AttachmentMixin from '../../../mixins/AttachmentMixin.js'

const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'

const attachments = [
	{ downloadUrl: '/cat.png', fileName: 'cat.png', mime: 'image/png' },
	{ downloadUrl: '/notes.txt', fileName: 'notes.txt', mime: 'text/plain' },
	{ downloadUrl: '/invoice.pdf', fileName: 'invoice.pdf', mime: 'application/pdf' },
	{ downloadUrl: '/report.docx', fileName: 'report.docx', mime: DOCX_MIME },
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

	it('does not preview office attachments without a source-capable handler', () => {
		global.OCA = {
			Viewer: {
				mimetypes: [DOCX_MIME],
				availableHandlers: [{ id: 'onlyoffice', mimes: [DOCX_MIME] }],
			},
		}

		const view = mountMixin()

		expect(view.vm.previewableFileInfos).toEqual([])
		expect(view.vm.canPreview(view.vm.fileInfos[3])).toBe(false)
	})

	it('opens office attachments with a source-capable handler', () => {
		const openWith = vi.fn()
		const open = vi.fn()
		global.OCA = {
			Viewer: {
				mimetypes: [DOCX_MIME, 'image/png'],
				availableHandlers: [
					{ id: 'onlyoffice', mimes: [DOCX_MIME] },
					{ id: 'eurooffice', mimes: [DOCX_MIME], supportsSource: true },
				],
				openWith,
				open,
			},
		}

		const view = mountMixin()
		const fileInfo = view.vm.fileInfos[3]

		expect(view.vm.canPreview(fileInfo)).toBe(true)

		view.vm.showViewer(fileInfo)

		expect(openWith).toHaveBeenCalledWith('eurooffice', {
			fileInfo,
			list: view.vm.previewableFileInfos,
		})
		expect(fileInfo.source).toBe('/report.docx')
		expect(fileInfo.basename).toBe('report.docx')
		expect(open).not.toHaveBeenCalled()

		view.vm.showViewer(view.vm.fileInfos[0])

		expect(open).toHaveBeenCalledTimes(1)
	})
})
