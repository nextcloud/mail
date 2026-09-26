/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import mitt from 'mitt'
import { createPinia, setActivePinia } from 'pinia'
import Mailbox from '../../../components/Mailbox.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

const envelope = (databaseId, dateInt) => ({ databaseId, dateInt, flags: {} })

describe('Mailbox selection', () => {
	let wrapper

	const mountMailbox = ({ envelopes = [], groupEnvelopes = [], threadId } = {}) => {
		wrapper = shallowMount(Mailbox, {
			propsData: {
				account: {},
				mailbox: { databaseId: 1 },
				bus: mitt(),
				groupEnvelopes,
			},
			computed: {
				envelopes: () => envelopes,
			},
			mocks: {
				$route: { params: { threadId } },
			},
			localVue,
		})
		return wrapper.vm
	}

	beforeEach(() => {
		setActivePinia(createPinia())
		useMainStore().setHasFetchedInitialEnvelopesMutation(true)
	})

	afterEach(() => {
		wrapper.destroy()
	})

	it('selects all visible envelopes', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.selectAll()

		expect(vm.selection).toEqual([1, 2, 3])
	})

	it('toggles single envelopes and keeps display order', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.onSelect(3, true)
		vm.onSelect(1, true)
		vm.onSelect(3, false)

		expect(vm.selection).toEqual([1])
	})

	it('selects a range across date groups', () => {
		const vm = mountMailbox({
			groupEnvelopes: [
				['today', [envelope(1, 4), envelope(2, 3)]],
				['yesterday', [envelope(3, 2), envelope(4, 1)]],
			],
		})

		vm.onSelect(2, true)
		vm.onSelectRange(3, false)

		expect(vm.groupFlatIndices).toEqual([0, 2])
		expect(vm.selection).toEqual([2, 3, 4])
	})

	it('selects a range across date groups in oldest first order', () => {
		useMainStore().savePreferenceMutation({ key: 'sort-order', value: 'oldest' })
		const vm = mountMailbox({
			groupEnvelopes: [
				['yesterday', [envelope(4, 1), envelope(3, 2)]],
				['today', [envelope(2, 3), envelope(1, 4)]],
			],
		})

		vm.onSelect(3, true)
		vm.onSelectRange(2, false)

		expect(vm.flatEnvelopeList.map((e) => e.databaseId)).toEqual([4, 3, 2, 1])
		expect(vm.selection).toEqual([3, 2])
	})

	it('ignores a range without anchor', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })

		vm.onSelectRange(2, false)

		expect(vm.selection).toEqual([])
	})

	it('deselects a range', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)] })
		vm.selectAll()

		vm.onSelect(1, true)
		vm.onSelectRange(1, true)

		expect(vm.selection).toEqual([3])
	})

	it('uses the opened thread as range anchor', () => {
		const vm = mountMailbox({
			envelopes: [envelope(1, 3), envelope(2, 2), envelope(3, 1)],
			threadId: '3',
		})

		vm.onSelectRange(1, false)

		expect(vm.selection).toEqual([2, 3])
	})

	it('ignores envelopes that are not shown', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 2), envelope(2, 1)] })

		vm.setSelection([2, 42])

		expect(vm.selection).toEqual([2])
	})

	it('clears the selection', () => {
		const vm = mountMailbox({ envelopes: [envelope(1, 2), envelope(2, 1)] })
		vm.selectAll()

		vm.unselectAll()

		expect(vm.selection).toEqual([])
		expect(vm.selectionAnchor).toBeUndefined()
	})
})
