/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import mitt from 'mitt'
import { vi } from 'vitest'
import ComposerAttachments from '../../../components/ComposerAttachments.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { shareFile } from '../../../service/FileSharingService.js'

vi.mock('../../../service/AttachmentService.js')
vi.mock('../../../service/FileSharingService.js')

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

describe('ComposerAttachments', () => {
	let view

	beforeEach(() => {
		vi.clearAllMocks()
		view = shallowMount(ComposerAttachments, {
			localVue,
			propsData: {
				value: [],
				bus: mitt(),
			},
		})
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
