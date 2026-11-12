/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { flushPromises, shallowMount } from '@vue/test-utils'
import mitt from 'mitt'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ComposerAttachments from '../../../components/ComposerAttachments.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { uploadLocalAttachment } from '../../../service/AttachmentService.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))
vi.mock('../../../service/AttachmentService.js')

describe('ComposerAttachments', () => {
	let view

	beforeEach(() => {
		vi.clearAllMocks()
		view = shallowMount(ComposerAttachments, {
			props: {
				modelValue: [],
				bus: mitt(),
			},
			global: {
				mixins: [Nextcloud],
			},
		})
	})

	it('blocks sending while an upload is in progress and releases it afterwards', async () => {
		uploadLocalAttachment.mockImplementation((file) => Promise.resolve({ file, id: 1 }))

		const done = view.vm.addLocalFiles([new File(['a'], 'a.txt')])
		await view.vm.$nextTick()

		expect(view.emitted('update:blocksSending')).toEqual([[true]])

		await done
		await flushPromises()

		expect(view.emitted('update:blocksSending')).toEqual([[true], [false]])
		expect(view.emitted('update:modelValue')[0][0]).toEqual([expect.objectContaining({ id: 1, fileName: 'a.txt' })])
		expect(showError).not.toHaveBeenCalled()
	})

	it('reports a failed upload and keeps blocking sending', async () => {
		uploadLocalAttachment.mockRejectedValue(new Error('Request failed with status code 400'))

		await view.vm.addLocalFiles([new File(['a'], 'a.txt')])
		await flushPromises()

		expect(view.vm.attachments[0].error).toBe(true)
		expect(showError).toHaveBeenCalledWith('Could not upload a.txt')
		expect(view.emitted('update:modelValue')).toBeUndefined()
		expect(view.emitted('update:blocksSending')).toEqual([[true]])
	})

	it('blocks sending after a failed upload even if the error state was read before', async () => {
		let fail
		uploadLocalAttachment.mockReturnValue(new Promise((resolve, reject) => {
			fail = reject
		}))

		const done = view.vm.addLocalFiles([new File(['a'], 'a.txt')])
		expect(view.vm.hasAttachmentErrors).toBe(false)
		fail(new Error('Request failed with status code 400'))
		await done
		await flushPromises()

		expect(view.vm.hasAttachmentErrors).toBe(true)
		expect(view.vm.blocksSending).toBe(true)
	})

	it('blocks sending until every batch is uploaded', async () => {
		let finishSecond
		uploadLocalAttachment
			.mockImplementationOnce((file) => Promise.resolve({ file, id: 1 }))
			.mockImplementationOnce((file) => new Promise((resolve) => {
				finishSecond = () => resolve({ file, id: 2 })
			}))

		const first = view.vm.addLocalFiles([new File(['a'], 'a.txt')])
		const second = view.vm.addLocalFiles([new File(['b'], 'b.txt')])
		await first
		await flushPromises()

		expect(view.vm.blocksSending).toBe(true)

		finishSecond()
		await second
		await flushPromises()

		expect(view.vm.blocksSending).toBe(false)
	})

	it('does not report an upload aborted by removing the attachment', async () => {
		uploadLocalAttachment
			.mockImplementationOnce((file, accountId, progress, controller) => new Promise((resolve, reject) => {
				controller.signal.addEventListener('abort', () => reject(new Error('canceled')))
			}))
			.mockImplementationOnce((file) => Promise.resolve({ file, id: 2 }))

		const done = view.vm.addLocalFiles([new File(['a'], 'a.txt'), new File(['b'], 'b.txt')])
		view.vm.onDelete(view.vm.attachments[0])
		await done
		await flushPromises()

		expect(showError).not.toHaveBeenCalled()
		expect(view.emitted('update:blocksSending')).toEqual([[true], [false]])
	})
})
