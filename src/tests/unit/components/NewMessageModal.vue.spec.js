/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NewMessageModal from '../../../components/NewMessageModal.vue'
import useMainStore from '../../../store/mainStore.js'

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

	it('patches the body the composer emits on teardown instead of throwing', () => {
		const view = mountModal({ data: { isHtml: false }, composerOpen: false })

		expect(() => view.vm.patchEditorBody('a last keystroke')).not.toThrow()
		expect(view.vm.mainStore.patchComposerData).toHaveBeenCalledWith({
			bodyPlain: 'a last keystroke',
			isHtml: false,
		})
	})
})
