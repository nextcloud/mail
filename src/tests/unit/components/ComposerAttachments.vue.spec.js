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
import { shareFile } from '../../../service/FileSharingService.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))
vi.mock('../../../service/AttachmentService.js')
vi.mock('../../../service/FileSharingService.js')

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

	it('inserts a share link for every picked file', async () => {
		shareFile.mockImplementation((path) => Promise.resolve(`https://cloud.example.com/s${path}`))
		const appendToBody = vi.fn()
		view.props('bus').on('append-to-body-at-cursor', appendToBody)

		await view.vm.onAddCloudAttachmentLink([{ path: '/a.txt' }, { path: '/b.txt' }])

		expect(shareFile).toHaveBeenCalledTimes(2)
		expect(appendToBody).toHaveBeenCalledWith('<a href="https://cloud.example.com/s/a.txt">https://cloud.example.com/s/a.txt</a><br><a href="https://cloud.example.com/s/b.txt">https://cloud.example.com/s/b.txt</a>')
	})

	it('inserts the link of a single picked file', async () => {
		shareFile.mockResolvedValue('https://cloud.example.com/s/a')
		const appendToBody = vi.fn()
		view.props('bus').on('append-to-body-at-cursor', appendToBody)

		await view.vm.onAddCloudAttachmentLink([{ path: '/a.txt' }])

		expect(appendToBody).toHaveBeenCalledTimes(1)
		expect(appendToBody).toHaveBeenCalledWith('<a href="https://cloud.example.com/s/a">https://cloud.example.com/s/a</a>')
	})

	it('still inserts the links of shares that succeeded when a later share fails', async () => {
		shareFile
			.mockResolvedValueOnce('https://cloud.example.com/s/a')
			.mockRejectedValueOnce(new Error('share failed'))
			.mockResolvedValueOnce('https://cloud.example.com/s/c')
		const appendToBody = vi.fn()
		view.props('bus').on('append-to-body-at-cursor', appendToBody)

		await view.vm.onAddCloudAttachmentLink([{ path: '/a.txt' }, { path: '/b.txt' }, { path: '/c.txt' }])

		expect(shareFile).toHaveBeenCalledTimes(3)
		expect(appendToBody).toHaveBeenCalledWith('<a href="https://cloud.example.com/s/a">https://cloud.example.com/s/a</a><br><a href="https://cloud.example.com/s/c">https://cloud.example.com/s/c</a>')
	})

	it('inserts nothing when every share fails', async () => {
		shareFile.mockRejectedValue(new Error('share failed'))
		const appendToBody = vi.fn()
		view.props('bus').on('append-to-body-at-cursor', appendToBody)

		await view.vm.onAddCloudAttachmentLink([{ path: '/a.txt' }, { path: '/b.txt' }])

		expect(shareFile).toHaveBeenCalledTimes(2)
		expect(appendToBody).not.toHaveBeenCalled()
	})

	it('keeps the sharing flag on during the loop and clears it afterwards', async () => {
		let finishFirst
		let finishSecond
		shareFile
			.mockImplementationOnce(() => new Promise((resolve) => { finishFirst = resolve }))
			.mockImplementationOnce(() => new Promise((resolve) => { finishSecond = resolve }))

		const done = view.vm.onAddCloudAttachmentLink([{ path: '/a.txt' }, { path: '/b.txt' }])
		expect(view.vm.sharingLinks).toBe(true)

		finishFirst('https://cloud.example.com/s/a')
		await flushPromises()
		expect(view.vm.sharingLinks).toBe(true)

		finishSecond('https://cloud.example.com/s/b')
		await done

		expect(view.vm.sharingLinks).toBe(false)
		expect(view.emitted('update:sharingLinks')).toEqual([[true], [false]])
	})

	it('clears the sharing flag when a share fails', async () => {
		shareFile.mockRejectedValue(new Error('share failed'))

		await view.vm.onAddCloudAttachmentLink([{ path: '/a.txt' }])

		expect(view.vm.sharingLinks).toBe(false)
	})
})
