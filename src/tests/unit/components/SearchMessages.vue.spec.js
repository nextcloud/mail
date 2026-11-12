/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import SearchMessages from '../../../components/SearchMessages.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('SearchMessages', () => {
	let store
	let pinia

	const mount = () => shallowMount(SearchMessages, {
		props: {
			mailbox: { databaseId: 5 },
			accountId: 1,
		},
		global: {
			plugins: [pinia],
			mixins: [Nextcloud],
		},
	})

	const typeQuery = async (view, query) => {
		view.vm.query = query
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()
	}

	beforeEach(() => {
		vi.useFakeTimers()
		pinia = createTestingPinia()
		setActivePinia(pinia)
		store = useMainStore()
		store.getAccount = vi.fn().mockReturnValue({ accountId: 1, emailAddress: 'user@example.com', searchBody: false })
		store.getPreference = vi.fn().mockReturnValue('false')
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('searches with the typed text', async () => {
		const view = mount()

		await typeQuery(view, 'icloud.com')

		expect(view.emitted('searchChanged')).toEqual([
			['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'],
		])
	})

	it('clears the text filters when the search text is deleted', async () => {
		const view = mount()
		await typeQuery(view, 'icloud.com')

		await typeQuery(view, '')

		expect(view.emitted('searchChanged').at(-1)).toEqual(['mentions:false match:allof'])
	})

	it('keeps other filters when the search text is deleted', async () => {
		const view = mount()
		view.vm.searchFlags = ['unread']
		await typeQuery(view, 'icloud.com')

		await typeQuery(view, '')

		expect(view.emitted('searchChanged').at(-1)).toEqual(['flags:unread mentions:false match:allof'])
	})

	it('keeps a filter changed after typing when the search text is deleted', async () => {
		const view = mount()
		await typeQuery(view, 'icloud.com')
		view.vm.toggleCurrentUser()
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()

		await typeQuery(view, '')

		expect(view.emitted('searchChanged').at(-1)).toEqual(['to:user@example.com mentions:false match:allof'])
	})

	it('searches only once when the filter is reset', async () => {
		const view = mount()
		await typeQuery(view, 'icloud.com')

		view.vm.resetFilter()
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()

		expect(view.emitted('searchChanged')).toHaveLength(2)
		expect(view.emitted('searchChanged').at(-1)).toEqual(['mentions:false match:allof'])
	})
})
