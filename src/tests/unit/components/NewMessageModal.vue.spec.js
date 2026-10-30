/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showLoading, showSuccess } from '@nextcloud/dialogs'
import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NewMessageModal from '../../../components/NewMessageModal.vue'
import { saveDraft } from '../../../service/DraftService.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showLoading: vi.fn(),
	showSuccess: vi.fn(),
}))

vi.mock('../../../service/DraftService.js', () => ({
	deleteDraft: vi.fn(),
	saveDraft: vi.fn(),
	updateDraft: vi.fn(),
}))

const ComposerStub = {
	name: 'Composer',
	template: '<div />',
	methods: {
		getMessageData() {
			return { isHtml: true }
		},
	},
}

/**
 * Mount the modal around a composer that is either rendered or already gone.
 *
 * @param {object} options - test options
 * @param {object} options.data - the composer data held by the store
 * @param {boolean} options.composerOpen - whether the composer is still rendered
 * @return {object} the mounted wrapper
 */
function mountModal({ data, composerOpen }) {
	const store = useMainStore()
	store.newMessage = { type: 'imap', data, options: {} }
	store.showMessageComposer = composerOpen
	store.patchComposerData = vi.fn()

	return shallowMount(NewMessageModal, {
		props: {
			accounts: [],
		},
		global: {
			mocks: {
				t: (app, text) => text,
			},
			stubs: {
				Composer: ComposerStub,
				KeepAlive: false,
			},
		},
	})
}

describe('NewMessageModal', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
	})

	it('cooks the format off the composer while it is still rendered', async () => {
		const view = mountModal({ data: { isHtml: false }, composerOpen: true })

		await view.vm.patchComposerData({ subject: 'Hello' })

		expect(view.vm.mainStore.patchComposerData).toHaveBeenCalledWith({ subject: 'Hello', isHtml: true })
	})

	it('falls back to the stored format once the composer is torn down', async () => {
		const view = mountModal({ data: { isHtml: true }, composerOpen: false })

		await view.vm.patchComposerData({ bodyHtml: '<p>Bye</p>' })

		expect(view.vm.mainStore.patchComposerData).toHaveBeenCalledWith({ bodyHtml: '<p>Bye</p>', isHtml: true })
	})

	describe('closing a changed draft', () => {
		let savingToast
		let view

		beforeEach(() => {
			savingToast = { hideToast: vi.fn() }
			showLoading.mockReturnValue(savingToast)
			view = mountModal({ data: { isHtml: false }, composerOpen: true })
			view.vm.mainStore.composerMessageIsSaved = true
			view.vm.changed = true
			view.vm.draftSaved = true
			vi.spyOn(view.vm.mainStore, 'showMessageComposerMutation')
		})

		it('shows a saving toast until the draft is stored', async () => {
			view.vm.mainStore.stopComposerSession = vi.fn().mockResolvedValue()

			await view.vm.onClose()

			expect(showLoading).toHaveBeenCalledWith('Saving draft…')
			expect(savingToast.hideToast).toHaveBeenCalled()
			expect(showSuccess).toHaveBeenCalledWith('Draft saved')
			expect(view.vm.mainStore.showMessageComposerMutation).not.toHaveBeenCalled()
		})

		it('reopens the composer when storing the draft fails', async () => {
			view.vm.mainStore.stopComposerSession = vi.fn().mockRejectedValue(new Error('IMAP unavailable'))

			await view.vm.onClose()

			expect(savingToast.hideToast).toHaveBeenCalled()
			expect(view.vm.mainStore.composerMessage.indicatorDisabled).toBe(false)
			expect(view.vm.mainStore.showMessageComposerMutation).toHaveBeenCalled()
			expect(showError).toHaveBeenCalledWith('Failed to save draft')
		})

		it('saves pending changes under the saving toast only', async () => {
			view.vm.mainStore.composerMessageIsSaved = false
			view.vm.cookedComposerData = { isHtml: false, attachments: [] }
			saveDraft.mockResolvedValue({ id: 7 })
			view.vm.mainStore.stopComposerSession = vi.fn().mockResolvedValue()

			await view.vm.onClose()

			expect(saveDraft).toHaveBeenCalled()
			expect(showLoading).toHaveBeenCalledTimes(1)
			expect(showSuccess).toHaveBeenCalledTimes(1)
			expect(showSuccess).toHaveBeenCalledWith('Draft saved')
			expect(view.vm.mainStore.stopComposerSession).toHaveBeenCalledWith(expect.objectContaining({ moveToImap: true }))
		})

		it('reopens the composer when saving pending changes fails', async () => {
			view.vm.mainStore.composerMessageIsSaved = false
			view.vm.cookedComposerData = { isHtml: false, attachments: [] }
			saveDraft.mockRejectedValue(new Error('Database unavailable'))
			view.vm.mainStore.stopComposerSession = vi.fn()

			await view.vm.onClose()

			expect(view.vm.mainStore.stopComposerSession).not.toHaveBeenCalled()
			expect(savingToast.hideToast).toHaveBeenCalled()
			expect(view.vm.mainStore.showMessageComposerMutation).toHaveBeenCalled()
			expect(showError).toHaveBeenCalledTimes(1)
			expect(showError).toHaveBeenCalledWith('Failed to save draft')
		})
	})

	describe('sending', () => {
		const message = { subject: 'Hello', attachments: [], isHtml: false, bodyPlain: 'Hi there', accountId: 1 }
		let sendingToast
		let view

		beforeEach(() => {
			sendingToast = { hideToast: vi.fn() }
			showLoading.mockReturnValue(sendingToast)
			view = mountModal({ data: { isHtml: false }, composerOpen: true })
			vi.spyOn(view.vm.mainStore, 'showMessageComposerMutation')
			view.vm.mainStore.stopComposerSession = vi.fn().mockResolvedValue()
			view.vm.outboxStore.enqueueFromDraft = vi.fn().mockResolvedValue()
			view.vm.outboxStore.sendMessageWithUndo = vi.fn().mockResolvedValue()
		})

		it('hides the composer behind a sending toast', async () => {
			saveDraft.mockResolvedValue({ id: 7 })

			await view.vm.onSend(message)

			expect(view.vm.mainStore.showMessageComposer).toBe(false)
			expect(showLoading).toHaveBeenCalledWith('Preparing message…')
			expect(view.vm.outboxStore.enqueueFromDraft).toHaveBeenCalled()
			expect(sendingToast.hideToast).toHaveBeenCalled()
			expect(view.vm.mainStore.showMessageComposerMutation).not.toHaveBeenCalled()
		})

		it('keeps the composer open when the subject is missing', async () => {
			await view.vm.onSend({ ...message, subject: '' })

			expect(view.vm.mainStore.showMessageComposer).toBe(true)
			expect(showLoading).not.toHaveBeenCalled()
			expect(view.vm.warning).toBe('Your message has no subject. Do you want to send it anyway?')
		})

		it('reopens the composer when sending fails', async () => {
			saveDraft.mockRejectedValue(new Error('Database unavailable'))

			await view.vm.onSend(message)

			expect(sendingToast.hideToast).toHaveBeenCalled()
			expect(view.vm.mainStore.composerMessage.indicatorDisabled).toBe(false)
			expect(view.vm.mainStore.showMessageComposerMutation).toHaveBeenCalled()
			expect(view.vm.mainStore.stopComposerSession).not.toHaveBeenCalled()
		})
	})

	describe('leaving the page with unsaved changes', () => {
		let event
		let view

		beforeEach(() => {
			event = { preventDefault: vi.fn() }
			view = mountModal({ data: { isHtml: false }, composerOpen: false })
			view.vm.changed = true
			vi.spyOn(view.vm.mainStore, 'showMessageComposerMutation')
		})

		it('warns and reopens the composer', () => {
			view.vm.onBeforeUnload(event)

			expect(event.preventDefault).toHaveBeenCalled()
			expect(view.vm.mainStore.showMessageComposerMutation).toHaveBeenCalled()
		})

		it('warns but keeps the composer closed while it is being saved', () => {
			view.vm.mainStore.setComposerIndicatorDisabledMutation(true)

			view.vm.onBeforeUnload(event)

			expect(event.preventDefault).toHaveBeenCalled()
			expect(view.vm.mainStore.showMessageComposerMutation).not.toHaveBeenCalled()
		})
	})

	it('closes an unchanged draft without a saving toast', async () => {
		const view = mountModal({ data: { isHtml: false }, composerOpen: true })
		view.vm.mainStore.stopComposerSession = vi.fn().mockResolvedValue()

		await view.vm.onClose()

		expect(showLoading).not.toHaveBeenCalled()
		expect(showSuccess).not.toHaveBeenCalled()
		expect(view.vm.mainStore.stopComposerSession).toHaveBeenCalled()
	})

	it('patches the body the composer emits on teardown instead of throwing', () => {
		const view = mountModal({ data: { isHtml: false }, composerOpen: false })

		expect(() => view.vm.patchEditorBody('a last keystroke')).not.toThrow()
		expect(view.vm.mainStore.patchComposerData).toHaveBeenCalledWith({
			bodyPlain: 'a last keystroke',
			isHtml: false,
		})
	})
})
