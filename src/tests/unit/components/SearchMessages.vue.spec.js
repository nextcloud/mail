/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import { PiniaVuePlugin, setActivePinia } from 'pinia'
import SearchMessages from '../../../components/SearchMessages.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()
localVue.use(PiniaVuePlugin)
localVue.mixin(Nextcloud)

describe('SearchMessages', () => {
	let store

	const mount = () => shallowMount(SearchMessages, {
		propsData: {
			mailbox: { databaseId: 5 },
			accountId: 1,
		},
		localVue,
	})

	const typeQuery = async (view, query) => {
		view.setData({ query })
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()
	}

	beforeEach(() => {
		vi.useFakeTimers()
		setActivePinia(createTestingPinia())
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

		expect(view.emitted('search-changed')).toEqual([
			['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'],
		])
	})

	it('clears the text filters when the search text is deleted', async () => {
		const view = mount()
		await typeQuery(view, 'icloud.com')

		await typeQuery(view, '')

		expect(view.emitted('search-changed').at(-1)).toEqual(['mentions:false match:allof'])
	})

	it('keeps other filters when the search text is deleted', async () => {
		const view = mount()
		view.setData({ searchFlags: ['unread'] })
		await typeQuery(view, 'icloud.com')

		await typeQuery(view, '')

		expect(view.emitted('search-changed').at(-1)).toEqual(['flags:unread mentions:false match:allof'])
	})

	it('keeps a filter changed after typing when the search text is deleted', async () => {
		const view = mount()
		await typeQuery(view, 'icloud.com')
		view.vm.toggleCurrentUser()
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()

		await typeQuery(view, '')

		expect(view.emitted('search-changed').at(-1)).toEqual(['to:user@example.com mentions:false match:allof'])
	})

	it('searches only once when the filter is reset', async () => {
		const view = mount()
		await typeQuery(view, 'icloud.com')

		view.vm.resetFilter()
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()

		expect(view.emitted('search-changed')).toHaveLength(2)
		expect(view.emitted('search-changed').at(-1)).toEqual(['mentions:false match:allof'])
	})
})
