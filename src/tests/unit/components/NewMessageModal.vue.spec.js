/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import NewMessageModal from '../../../components/NewMessageModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'
import useOutboxStore from '../../../store/outboxStore.js'

vi.mock('../../../service/DraftService.js', () => ({
	saveDraft: vi.fn().mockResolvedValue({ id: 42 }),
	updateDraft: vi.fn().mockResolvedValue(undefined),
	deleteDraft: vi.fn().mockResolvedValue(undefined),
}))

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

// Render the NcModal stub's default slot so .modal-content is present in the DOM
const modalStub = {
	template: '<div class="modal-stub"><slot /></div>',
}

describe('NewMessageModal', () => {
	let store
	let outboxStore

	const mountModal = () => shallowMount(NewMessageModal, {
		localVue,
		propsData: { accounts: [] },
		stubs: { Modal: modalStub },
	})

	beforeEach(() => {
		vi.useFakeTimers()
		setActivePinia(createPinia())

		store = useMainStore()
		store.newMessage = {
			type: 'outbox',
			data: { id: 5, type: 0, accountId: 1, to: [], cc: [], bcc: [], attachments: [], isHtml: false, bodyPlain: 'x' },
		}
		store.showMessageComposer = true
		store.getPreference = vi.fn().mockReturnValue('normal')
		store.stopComposerSession = vi.fn().mockResolvedValue(undefined)

		outboxStore = useOutboxStore()
		outboxStore.updateMessage = vi.fn().mockResolvedValue(undefined)
		outboxStore.enqueueFromDraft = vi.fn().mockResolvedValue(undefined)
		outboxStore.sendMessageWithUndo = vi.fn().mockResolvedValue(undefined)
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('adds the fly-in class on the modal while composerFlyIn is set (Undo send reopen)', async () => {
		const wrapper = mountModal()

		store.composerFlyIn = true
		await wrapper.vm.$nextTick()

		expect(wrapper.find('.modal-stub').classes()).toContain('composer-fly-in')
	})

	it('adds the fly-up class on the modal while flying (send)', async () => {
		const wrapper = mountModal()

		await wrapper.setData({ flying: true })

		expect(wrapper.find('.modal-stub').classes()).toContain('composer-fly-up')
	})

	it('onComposerAnimationEnd clears the flag only for the fly-in animation', () => {
		const wrapper = mountModal()
		store.composerFlyIn = true

		wrapper.vm.onComposerAnimationEnd({ animationName: 'composer-fly-up-abc' })
		expect(store.composerFlyIn).toBe(true)

		wrapper.vm.onComposerAnimationEnd({ animationName: 'composer-fly-in-abc' })
		expect(store.composerFlyIn).toBe(false)
	})

	it('plays the fly-up animation before hiding the composer on send', async () => {
		const wrapper = mountModal()
		let flyingWhenSent
		outboxStore.sendMessageWithUndo = vi.fn(() => {
			flyingWhenSent = wrapper.vm.flying
			return Promise.resolve()
		})

		const data = { id: 5, type: 0, accountId: 1, to: [], cc: [], bcc: [], attachments: [], subject: 'Hi', isHtml: false, bodyPlain: 'body', sendAt: 0 }
		const promise = wrapper.vm.onSend(data)
		await vi.runAllTimersAsync()
		await promise

		expect(outboxStore.sendMessageWithUndo).toHaveBeenCalled()
		expect(flyingWhenSent).toBe(true)
		expect(store.stopComposerSession).toHaveBeenCalled()
		expect(wrapper.vm.flying).toBe(false)
	})
})
