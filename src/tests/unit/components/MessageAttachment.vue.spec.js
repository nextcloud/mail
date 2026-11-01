/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import MessageAttachment from '../../../components/MessageAttachment.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { saveAttachmentToFiles } from '../../../service/AttachmentService.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

vi.mock('@nextcloud/dialogs', () => ({
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('../../../service/AttachmentService.js', () => ({
	downloadAttachment: vi.fn(),
	saveAttachmentToFiles: vi.fn().mockResolvedValue(undefined),
}))
vi.mock('../../../util/filePicker.js', () => ({
	pickFolder: vi.fn().mockResolvedValue('/Documents'),
}))

describe('MessageAttachment', () => {
	it('saves the attachment of its own message, not of the message in the route', async () => {
		const view = shallowMount(MessageAttachment, {
			localVue,
			propsData: {
				id: '2',
				messageId: 41,
				fileName: 'price list.pdf',
				url: '/apps/mail/api/messages/41/attachment/2',
				size: 100,
				mime: 'application/pdf',
				mimeUrl: '',
			},
			mocks: { $route: { params: { threadId: 40 } } },
		})

		await view.vm.saveToCloud()

		expect(saveAttachmentToFiles).toHaveBeenCalledWith(41, '2', '/Documents')
	})
})
