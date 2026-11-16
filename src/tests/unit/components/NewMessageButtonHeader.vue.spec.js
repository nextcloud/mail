/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NewMessageButtonHeader from '../../../components/NewMessageButtonHeader.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'
import eventBus from '../../../util/eventBus.js'

describe('NewMessageButtonHeader', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()

		store.getMailbox = vi.fn().mockReturnValue({ databaseId: 5, accountId: 1 })
		store.getAccount = vi.fn().mockReturnValue({ id: 1 })
	})

	const mountHeader = ({ props = {}, route = { name: 'mailbox', params: { mailboxId: 5 } } } = {}) => mount(NewMessageButtonHeader, {
		props,
		global: {
			mixins: [Nextcloud],
			directives: { shortkey: {} },
			mocks: {
				$route: route,
			},
		},
	})

	it('shows the loading icon while the R shortcut refreshes', async () => {
		store.syncEnvelopes = vi.fn().mockReturnValue(new Promise(() => {}))
		const view = mountHeader()

		await view.find('.refresh__button').trigger('shortkey')

		expect(store.syncEnvelopes).toHaveBeenCalledWith({ mailboxId: 5 })
		expect(view.findComponent(NcLoadingIcon).exists()).toBe(true)
		expect(view.find('.refresh__button').attributes('disabled')).toBeDefined()
	})

	it('starts a blank message on the C shortcut', async () => {
		const view = mountHeader()

		await view.find('button').trigger('shortkey')

		expect(store.startComposerSession).toHaveBeenCalledWith({ isBlankMessage: true })
	})

	it('shows the loading icon while a mail:refresh request refreshes', async () => {
		store.syncEnvelopes = vi.fn().mockReturnValue(new Promise(() => {}))
		const view = mountHeader()

		eventBus.emit('mail:refresh')
		await view.vm.$nextTick()

		expect(store.syncEnvelopes).toHaveBeenCalledOnce()
		expect(view.findComponent(NcLoadingIcon).exists()).toBe(true)
		view.unmount()
	})

	it('ignores mail:refresh when the refresh button is hidden', () => {
		store.syncEnvelopes = vi.fn()
		const view = mountHeader({ props: { showRefresh: false } })

		eventBus.emit('mail:refresh')

		expect(store.syncEnvelopes).not.toHaveBeenCalled()
		view.unmount()
	})

	it('ignores mail:refresh outside a mailbox', () => {
		store.syncEnvelopes = vi.fn()
		const view = mountHeader({ route: { name: 'outbox', params: {} } })

		eventBus.emit('mail:refresh')

		expect(store.syncEnvelopes).not.toHaveBeenCalled()
		view.unmount()
	})

	it('stops listening to mail:refresh once unmounted', () => {
		store.syncEnvelopes = vi.fn()
		mountHeader().unmount()

		eventBus.emit('mail:refresh')

		expect(store.syncEnvelopes).not.toHaveBeenCalled()
	})
})
