/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Mailbox from '../../../components/Mailbox.vue'
import MailboxLockedError from '../../../errors/MailboxLockedError.js'
import MailboxNotCachedError from '../../../errors/MailboxNotCachedError.js'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('Mailbox', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.hasFetchedInitialEnvelopes = true
		store.getEnvelopes = vi.fn().mockReturnValue([])
		store.fetchEnvelopes = vi.fn()
			.mockRejectedValueOnce(new MailboxNotCachedError('22 is not cached'))
			.mockResolvedValue([])
		store.syncEnvelopes = vi.fn()
		store.updateSyncTimestamp = vi.fn()
	})

	afterEach(() => {
		vi.restoreAllMocks()
	})

	function mountMailbox() {
		return shallowMount(Mailbox, {
			props: {
				account: { id: 4 },
				mailbox: { databaseId: 22, accountId: 4 },
				bus: { on: vi.fn(), off: vi.fn() },
				searchQuery: 'is:pi-other',
			},
			global: {
				mixins: [Nextcloud],
			},
		})
	}

	it('initializes an uncached folder and then loads its messages', async () => {
		store.syncEnvelopes.mockResolvedValue([])
		const view = mountMailbox()

		await view.vm.loadEnvelopes()

		expect(store.syncEnvelopes).toHaveBeenCalledWith({ mailboxId: 22, query: 'is:pi-other', init: true })
		expect(store.fetchEnvelopes).toHaveBeenCalledTimes(2)
		expect(view.vm.loadingCacheInitialization).toBe(false)
		expect(view.vm.error).toBe(false)
	})

	it('loads the messages when another section is already initializing the folder', async () => {
		store.syncEnvelopes.mockRejectedValue(new MailboxLockedError('22 is already being synced'))
		const view = mountMailbox()

		await view.vm.loadEnvelopes()

		expect(store.syncEnvelopes).toHaveBeenCalledTimes(1)
		expect(store.fetchEnvelopes).toHaveBeenCalledTimes(2)
		expect(view.vm.loadingCacheInitialization).toBe(false)
		expect(view.vm.error).toBe(false)
	})

	it('shows an error when the initialization fails otherwise', async () => {
		const error = new Error('server error')
		store.syncEnvelopes.mockRejectedValue(error)
		const view = mountMailbox()

		await view.vm.loadEnvelopes()

		expect(store.fetchEnvelopes).toHaveBeenCalledTimes(1)
		expect(view.vm.error).toBe(error)
	})
})
