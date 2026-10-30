/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { enableAutoUnmount, shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { nextTick, reactive } from 'vue'
import App from '../../App.vue'
import Nextcloud from '../../mixins/Nextcloud.js'
import { SYNC_INTERVAL } from '../../store/constants.js'
import useMainStore from '../../store/mainStore.js'

vi.mock('../../service/AutoConfigService.js')
vi.mock('../../init.js')
vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

enableAutoUnmount(afterEach)

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

describe('App', () => {
	let store
	let view

	beforeEach(() => {
		setActivePinia(createTestingPinia())

		store = useMainStore()
		store.isExpiredSession = false

		view = mountApp()
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

	it('refreshes the trusted senders every sync interval', async () => {
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL * 2)

		expect(store.fetchTrustedSenders).toHaveBeenCalledTimes(2)
	})

	it('keeps refreshing the lists while an inbox sync hangs', async () => {
		store.syncInboxes.mockReturnValue(new Promise(() => {}))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL * 2)

		expect(store.fetchTrustedSenders).toHaveBeenCalledTimes(2)
	})

	it('waits for a refresh before it starts the next one', async () => {
		store.fetchTrustedSenders.mockReturnValue(new Promise(() => {}))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL * 2)

		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
	})

	it('keeps refreshing the lists after a refresh threw', async () => {
		store.fetchTrustedSenders.mockImplementationOnce(() => {
			throw new Error('Unexpected')
		})
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL * 2)

		expect(store.fetchTrustedSenders).toHaveBeenCalledTimes(2)
	})

	it('refreshes the lists every interval while the clock runs slightly behind', async () => {
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		vi.setSystemTime(Date.now() - 1)
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchTrustedSenders).toHaveBeenCalledTimes(2)
	})

	it('refreshes the internal addresses while they are shown', async () => {
		store.getPreference.mockImplementation((key, fallback) => (key === 'internal-addresses' ? 'true' : fallback))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchInternalAddresses).toHaveBeenCalledOnce()
	})

	it('leaves the trusted senders alone while every sender\'s images are shown', async () => {
		store.getPreference.mockImplementation((key, fallback) => (key === 'always-show-external-images' ? 'true' : fallback))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchTrustedSenders).not.toHaveBeenCalled()
		expect(store.syncInboxes).toHaveBeenCalledOnce()
	})

	it('reloads the trusted senders once images are no longer shown for every sender', async () => {
		const preferences = reactive({ 'always-show-external-images': 'true' })
		store.getPreference.mockImplementation((key, fallback) => preferences[key] ?? fallback)
		mountApp()

		preferences['always-show-external-images'] = 'false'
		await nextTick()

		expect(store.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith({ stale: true })
	})

	it('doesn\'t reload the trusted senders once images are shown for every sender', async () => {
		const preferences = reactive({ 'always-show-external-images': 'false' })
		store.getPreference.mockImplementation((key, fallback) => preferences[key] ?? fallback)
		mountApp()

		preferences['always-show-external-images'] = 'true'
		await nextTick()

		expect(store.fetchTrustedSenders).not.toHaveBeenCalled()
	})

	it('reloads the trusted senders once images are no longer shown for every sender during a catch-up', async () => {
		const preferences = reactive({ 'always-show-external-images': 'true', 'internal-addresses': 'true' })
		store.getPreference.mockImplementation((key, fallback) => preferences[key] ?? fallback)
		store.fetchInternalAddresses.mockReturnValue(new Promise(() => {}))
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)
		switchTab('visible')

		preferences['always-show-external-images'] = 'false'
		await nextTick()

		expect(store.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith({ stale: true })
	})

	it('only reloads the trusted senders once images are no longer shown for every sender', async () => {
		const preferences = reactive({ 'always-show-external-images': 'true', 'internal-addresses': 'true' })
		store.getPreference.mockImplementation((key, fallback) => preferences[key] ?? fallback)
		mountApp()

		preferences['always-show-external-images'] = 'false'
		await nextTick()

		expect(store.fetchInternalAddresses).not.toHaveBeenCalled()
	})

	it('leaves the internal addresses alone while they are hidden', async () => {
		store.getPreference.mockImplementation((key, fallback) => fallback)
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.fetchInternalAddresses).not.toHaveBeenCalled()
	})

	it('syncs the inboxes while a refresh hangs', async () => {
		store.fetchTrustedSenders.mockReturnValue(new Promise(() => {}))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.syncInboxes).toHaveBeenCalledOnce()
	})

	it('syncs the inboxes of a hidden tab without refreshing the lists', async () => {
		visibility = 'hidden'
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.syncInboxes).toHaveBeenCalledOnce()
		expect(store.fetchTrustedSenders).not.toHaveBeenCalled()
	})

	it('refreshes the lists when a tab becomes visible after missing a refresh', async () => {
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		switchTab('visible')

		expect(store.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith({ stale: true })
	})

	it('doesn\'t catch up when a tab is hidden', async () => {
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		switchTab('hidden')

		expect(store.fetchTrustedSenders).not.toHaveBeenCalled()
	})

	it('doesn\'t refresh the lists again right after catching up', async () => {
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL * 1.5)

		switchTab('visible')
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL / 2)

		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
	})

	it('doesn\'t refresh the lists again when switching tabs within one interval', async () => {
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		switchTab('hidden')
		switchTab('visible')

		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
	})

	it('doesn\'t start another catch-up while one is still loading', async () => {
		store.fetchTrustedSenders.mockReturnValue(new Promise(() => {}))
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)
		switchTab('visible')

		switchTab('hidden')
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)
		switchTab('visible')

		expect(store.fetchTrustedSenders).toHaveBeenCalledOnce()
	})

	it('catches up again once the previous catch-up finished', async () => {
		visibility = 'hidden'
		mountApp()
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)
		switchTab('visible')
		await vi.advanceTimersByTimeAsync(0)

		switchTab('hidden')
		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)
		switchTab('visible')

		expect(store.fetchTrustedSenders).toHaveBeenCalledTimes(2)
		expect(store.fetchTrustedSenders).toHaveBeenLastCalledWith({ stale: true })
	})

	it('syncs the inboxes even when a refresh fails', async () => {
		store.fetchTrustedSenders.mockRejectedValue(new Error('Request failed with status code 500'))
		mountApp()

		await vi.advanceTimersByTimeAsync(SYNC_INTERVAL)

		expect(store.syncInboxes).toHaveBeenCalledOnce()
	})
})
