/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showLoading, showSuccess, TOAST_DEFAULT_TIMEOUT } from '@nextcloud/dialogs'
import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NewMessageModal from '../../../components/NewMessageModal.vue'
import { saveDraft, updateDraft } from '../../../service/DraftService.js'
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
 * @param {string} [options.type] - the type of the composer session
 * @param {object} options.data - the composer data held by the store
 * @param {boolean} options.composerOpen - whether the composer is still rendered
 * @return {object} the mounted wrapper
 */
function mountModal({ type = 'imap', data, composerOpen }) {
	const store = useMainStore()
	store.newMessage = { type, data, options: {} }
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
	const message = { subject: 'Hello', attachments: [], isHtml: false, bodyPlain: 'Hi there', accountId: 1 }

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
			expect(showError).toHaveBeenCalledWith('Failed to save draft', { timeout: TOAST_DEFAULT_TIMEOUT * 4 })
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
			expect(showError).toHaveBeenCalledWith('Failed to save draft', { timeout: TOAST_DEFAULT_TIMEOUT * 4 })
		})
	})

	describe('editing an outbox message', () => {
		let view

		beforeEach(() => {
			view = mountModal({ type: 'outbox', data: { id: 42, isHtml: false }, composerOpen: true })
			view.vm.outboxStore.stopMessage = vi.fn()
		})

		it('saves changes to the outbox message and keeps it paused', async () => {
			await view.vm.onDraft({ subject: 'Edited', isHtml: false, bodyPlain: 'Body', attachments: [{ id: 5, type: 'local' }] })

			expect(view.vm.outboxStore.stopMessage).toHaveBeenCalledWith({
				message: expect.objectContaining({
					id: 42,
					subject: 'Edited',
					attachments: [{ id: 5, type: 'local' }],
				}),
			})
			expect(saveDraft).not.toHaveBeenCalled()
			expect(updateDraft).not.toHaveBeenCalled()
			expect(view.vm.draftSaved).toBe(true)
		})

		it('keeps saving to the outbox message after a failed save', async () => {
			view.vm.outboxStore.stopMessage.mockRejectedValueOnce(new Error('Database unavailable'))

			await view.vm.onDraft({ subject: 'First', isHtml: false, attachments: [] })
			await view.vm.onDraft({ subject: 'Second', isHtml: false, attachments: [] })

			expect(view.vm.outboxStore.stopMessage).toHaveBeenLastCalledWith({
				message: expect.objectContaining({ id: 42, subject: 'Second' }),
			})
			expect(saveDraft).not.toHaveBeenCalled()
			expect(view.vm.canSaveDraft).toBe(true)
		})

		it('saves pending changes to the outbox message when closing', async () => {
			view.vm.changed = true
			view.vm.cookedComposerData = { subject: 'Edited', isHtml: false, attachments: [] }
			view.vm.mainStore.stopComposerSession = vi.fn().mockResolvedValue()

			await view.vm.onClose()

			expect(view.vm.outboxStore.stopMessage).toHaveBeenCalledWith({ message: expect.objectContaining({ id: 42 }) })
			expect(updateDraft).not.toHaveBeenCalled()
			expect(view.vm.mainStore.stopComposerSession).toHaveBeenCalledWith(expect.objectContaining({ restoreOriginalSendAt: true }))
			expect(showSuccess).toHaveBeenCalledWith('Message saved')
		})
	})

	describe('sending', () => {
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
			expect(showError).not.toHaveBeenCalled()
		})

		it('reopens the composer when sending fails', async () => {
			saveDraft.mockRejectedValue(new Error('Database unavailable'))

			await view.vm.onSend(message)

			expect(sendingToast.hideToast).toHaveBeenCalled()
			expect(view.vm.mainStore.composerMessage.indicatorDisabled).toBe(false)
			expect(view.vm.mainStore.showMessageComposerMutation).toHaveBeenCalled()
			expect(view.vm.mainStore.stopComposerSession).not.toHaveBeenCalled()
			expect(showError).toHaveBeenCalledWith('Could not send message', { timeout: TOAST_DEFAULT_TIMEOUT * 4 })
		})
	})

	describe('sending an outbox message', () => {
		let view

		beforeEach(() => {
			showLoading.mockReturnValue({ hideToast: vi.fn() })
			view = mountModal({ type: 'outbox', data: { id: 42, isHtml: false }, composerOpen: true })
			view.vm.mainStore.stopComposerSession = vi.fn().mockResolvedValue()
			view.vm.outboxStore.updateMessage = vi.fn().mockResolvedValue()
			view.vm.outboxStore.enqueueFromDraft = vi.fn()
			view.vm.outboxStore.sendMessageWithUndo = vi.fn().mockResolvedValue()
		})

		it('updates the message in the outbox and sends it', async () => {
			await view.vm.onSend(message)

			expect(view.vm.outboxStore.updateMessage).toHaveBeenCalledWith({ message: expect.objectContaining({ id: 42 }), id: 42 })
			expect(updateDraft).not.toHaveBeenCalled()
			expect(view.vm.outboxStore.enqueueFromDraft).not.toHaveBeenCalled()
			expect(view.vm.outboxStore.sendMessageWithUndo).toHaveBeenCalledWith({ id: 42 })
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

	describe('saving a draft after an earlier save', () => {
		const message = { subject: 'Hello', attachments: [], isHtml: false, bodyPlain: 'Hi there', accountId: 1 }
		let view

		beforeEach(async () => {
			view = mountModal({ data: { isHtml: false }, composerOpen: true })
			saveDraft.mockResolvedValue({ id: 7 })
			await view.vm.onDraft(message)
		})

		it('keeps the draft id when a later save fails', async () => {
			updateDraft.mockRejectedValueOnce(new Error('Network error'))

			const id = await view.vm.onDraft(message)

			expect(id).toBe(7)
			expect(view.vm.canSaveDraft).toBe(false)
		})

		it('updates the existing draft instead of creating a duplicate after a failed save', async () => {
			updateDraft.mockRejectedValueOnce(new Error('Network error'))
			await view.vm.onDraft(message)
			updateDraft.mockResolvedValueOnce()

			await view.vm.onDraft(message)

			expect(saveDraft).toHaveBeenCalledTimes(1)
			expect(updateDraft).toHaveBeenLastCalledWith(expect.objectContaining({ id: 7 }))
			expect(view.vm.canSaveDraft).toBe(true)
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
