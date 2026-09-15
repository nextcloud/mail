/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import AttachmentMixin from '../../../mixins/AttachmentMixin.js'

const attachments = [
	{ downloadUrl: '/cat.png', fileName: 'cat.png', mime: 'image/png' },
	{ downloadUrl: '/notes.txt', fileName: 'notes.txt', mime: 'text/plain' },
	{ downloadUrl: '/invoice.pdf', fileName: 'invoice.pdf', mime: 'application/pdf' },
]

function mountMixin() {
	return shallowMount({
		mixins: [AttachmentMixin],
		data: () => ({ attachments }),
		render: (h) => h('div'),
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
})
