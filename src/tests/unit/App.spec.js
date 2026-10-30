/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import App from '../../App.vue'
import Nextcloud from '../../mixins/Nextcloud.js'
import { ADDRESS_LISTS_TIMEOUT, SYNC_INTERVAL } from '../../store/constants.js'
import useMainStore from '../../store/mainStore.js'

vi.mock('../../service/AutoConfigService.js')
vi.mock('../../init.js')
vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

describe('App', () => {
	let store
	let view

	beforeEach(() => {
		setActivePinia(createTestingPinia())

		store = useMainStore()
		store.isExpiredSession = false

		view = shallowMount(App, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$router: {
						replace: vi.fn(),
					},
				},
			},
		})
	})

	it('handles session expiry', async () => {
		// Stub and prevent the actual reload
		view.vm.reload = vi.fn()

		expect(view.vm.isExpiredSession).toBe(false)
		store.isExpiredSession = true
		expect(view.vm.isExpiredSession).toBe(true)
	})
})

describe('App background sync', () => {
	let store
	let visibility

	beforeEach(() => {
		vi.useFakeTimers()
		setActivePinia(createTestingPinia())
		store = useMainStore()
		store.isExpiredSession = false
		visibility = 'visible'
		vi.spyOn(document, 'visibilityState', 'get').mockImplementation(() => visibility)
	})

	afterEach(() => {
		vi.useRealTimers()
		vi.restoreAllMocks()
	})

	function switchTab(state) {
		visibility = state
		document.dispatchEvent(new Event('visibilitychange'))
	}

	function mountApp() {
		return shallowMount(App, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$router: {
						replace: vi.fn(),
					},
				},
			},
		})
	}

	it('refreshes the trusted senders before the inboxes sync', async () => {
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
		expect(store.syncInboxes).toHaveBeenCalledOnce()
		expect(store.fetchTrustedSenders.mock.invocationCallOrder[0])
			.toBeLessThan(store.syncInboxes.mock.invocationCallOrder[0])
	})

	it('refreshes the internal addresses while they are shown', async () => {
		store.getPreference.mockImplementation((key, fallback) => (key === 'internal-addresses' ? 'true' : fallback))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchInternalAddresses).toHaveBeenCalledOnce()
	})

	it('leaves the internal addresses alone while they are hidden', async () => {
		store.getPreference.mockImplementation((key, fallback) => fallback)
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchInternalAddresses).not.toHaveBeenCalled()
	})

	it('syncs the inboxes when a refresh takes too long', async () => {
		store.fetchTrustedSenders.mockReturnValue(new Promise(() => {}))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)
		expect(store.syncInboxes).not.toHaveBeenCalled()
		await vi.advanceTimersByTimeAsync(ADDRESS_LISTS_TIMEOUT)

		expect(store.syncInboxes).toHaveBeenCalledOnce()
	})

	it('syncs the inboxes of a hidden tab without refreshing the lists', async () => {
		visibility = 'hidden'
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.syncInboxes).toHaveBeenCalledOnce()
		expect(store.fetchTrustedSenders).not.toHaveBeenCalled()
	})

	it('refreshes the lists when a tab becomes visible after missing a sync', async () => {
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		switchTab('visible')

		expect(store.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith({ stale: true })
	})

	it('doesn\'t refresh the lists again in the sync right after catching up', async () => {
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL * 1.5)

		switchTab('visible')
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL / 2)

		expect(store.syncInboxes).toHaveBeenCalledTimes(2)
		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
	})

	it('doesn\'t refresh the lists again when switching tabs within one sync', async () => {
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		switchTab('hidden')
		switchTab('visible')

		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
	})

	it('syncs the inboxes even when a refresh fails', async () => {
		store.fetchTrustedSenders.mockRejectedValue(new Error('Request failed with status code 500'))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.syncInboxes).toHaveBeenCalledOnce()
	})
})
