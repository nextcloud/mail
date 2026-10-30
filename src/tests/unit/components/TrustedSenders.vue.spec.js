/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import TrustedSenders from '../../../components/TrustedSenders.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { fetchTrustedSenders, trustSender } from '../../../service/TrustedSenderService.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))
vi.mock('../../../service/TrustedSenderService.js')

describe('TrustedSenders', () => {
	enableAutoUnmount(afterEach)

	const alice = { id: 1, email: 'alice@example.test', type: 'individual' }
	const exampleDomain = { id: 2, email: 'example.test', type: 'domain' }

	let pinia
	let store

	beforeEach(() => {
		pinia = createPinia()
		setActivePinia(pinia)
		store = useMainStore()
	})

	afterEach(() => {
		vi.resetAllMocks()
	})

	async function mountList(errorHandler = undefined) {
		const wrapper = shallowMount(TrustedSenders, {
			global: {
				plugins: [pinia],
				mixins: [Nextcloud],
				config: { errorHandler },
				stubs: {
					NcListItem: false,
				},
			},
		})
		await flushPromises()
		return wrapper
	}

	function listedSenders(wrapper) {
		return wrapper.findAll('.list-item-content__name').map((name) => name.text())
	}

	it('lists the current trusted senders when opened', async () => {
		store.setTrustedSendersMutation([alice])
		fetchTrustedSenders.mockResolvedValue([exampleDomain, alice])

		const wrapper = await mountList()

		expect(fetchTrustedSenders).toHaveBeenCalledOnce()
		expect(listedSenders(wrapper)).toEqual(['alice@example.test', 'example.test'])
	})

	it('keeps listing the known trusted senders when they could not be fetched', async () => {
		store.setTrustedSendersMutation([alice])
		fetchTrustedSenders.mockRejectedValue(new Error('Request failed with status code 500'))
		const errorHandler = vi.fn()

		const wrapper = await mountList(errorHandler)

		expect(listedSenders(wrapper)).toEqual(['alice@example.test'])
		expect(errorHandler).not.toHaveBeenCalled()
	})

	it('removes a trusted sender', async () => {
		fetchTrustedSenders.mockResolvedValue([alice, exampleDomain])
		const wrapper = await mountList()

		wrapper.findAllComponents(NcButton)[0].vm.$emit('click')
		await flushPromises()

		expect(trustSender).toHaveBeenCalledWith('alice@example.test', 'individual', false)
		expect(listedSenders(wrapper)).toEqual(['example.test'])
		expect(store.trustedSenders).toEqual([exampleDomain])
	})

	it('keeps a sender listed and says so when it could not be removed', async () => {
		fetchTrustedSenders.mockResolvedValue([alice])
		trustSender.mockRejectedValue(new Error('Request failed with status code 500'))
		const wrapper = await mountList()

		wrapper.findComponent(NcButton).vm.$emit('click')
		await flushPromises()

		expect(showError).toHaveBeenCalledOnce()
		expect(listedSenders(wrapper)).toEqual(['alice@example.test'])
	})
})
