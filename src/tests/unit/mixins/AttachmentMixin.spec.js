/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import AttachmentMixin from '../../../mixins/AttachmentMixin.js'

const attachments = [
	{ downloadUrl: '/cat.png', fileName: 'cat.png', mime: 'image/png' },
	{ downloadUrl: '/notes.txt', fileName: 'notes.txt', mime: 'text/plain' },
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
		global.OCA = { Viewer: { mimetypes: ['image/png'] } }

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
})
