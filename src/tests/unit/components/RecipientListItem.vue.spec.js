/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import RecipientListItem from '../../../components/RecipientListItem.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('RecipientListItem', () => {
	let pinia
	let store

	beforeEach(() => {
		pinia = createPinia()
		setActivePinia(pinia)
		store = useMainStore()
	})

	function mountRecipient(email) {
		return shallowMount(RecipientListItem, {
			global: {
				plugins: [pinia],
				mixins: [Nextcloud],
			},
			props: {
				option: { email, label: email },
			},
		})
	}

	function highlightExternalAddresses(highlight) {
		store.savePreferenceMutation({ key: 'internal-addresses', value: highlight ? 'true' : 'false' })
	}

	function isMarkedExternal(view) {
		return view.find('.ncselect__tag--recipient').classes().includes('external')
	}

	it('marks an external recipient', () => {
		highlightExternalAddresses(true)

		const view = mountRecipient('bob@other.test')

		expect(isMarkedExternal(view)).toBe(true)
	})

	it('doesn\'t mark a recipient of an internal domain', () => {
		highlightExternalAddresses(true)
		store.addInternalAddressMutation({ id: 1, address: 'example.test', type: 'domain' })

		const view = mountRecipient('alice@example.test')

		expect(isMarkedExternal(view)).toBe(false)
	})

	it('doesn\'t mark anybody while external addresses aren\'t highlighted', () => {
		highlightExternalAddresses(false)

		const view = mountRecipient('bob@other.test')

		expect(isMarkedExternal(view)).toBe(false)
	})

	it('doesn\'t mark a recipient without an email address', () => {
		highlightExternalAddresses(true)

		const view = mountRecipient(undefined)

		expect(isMarkedExternal(view)).toBe(false)
	})

	it('updates the mark when the internal addresses change', async () => {
		highlightExternalAddresses(true)
		const view = mountRecipient('bob@other.test')

		store.addInternalAddressMutation({ id: 1, address: 'other.test', type: 'domain' })
		await view.vm.$nextTick()

		expect(isMarkedExternal(view)).toBe(false)
	})

	it('updates the mark when highlighting is turned on', async () => {
		highlightExternalAddresses(false)
		const view = mountRecipient('bob@other.test')

		highlightExternalAddresses(true)
		await view.vm.$nextTick()

		expect(isMarkedExternal(view)).toBe(true)
	})
})
