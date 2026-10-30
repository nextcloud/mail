/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { createTestingPinia } from '@pinia/testing'
import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { nextTick } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import InternalAddress from '../../../components/InternalAddress.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

describe('InternalAddress', () => {
	enableAutoUnmount(afterEach)

	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	async function mountWithAddress(address) {
		const wrapper = shallowMount(InternalAddress, {
			attachTo: document.body,
			global: {
				mixins: [Nextcloud],
				stubs: {
					NcInputField: false,
					NcTextField: false,
				},
			},
		})
		await wrapper.findComponent(NcTextField).setValue(address)
		return wrapper
	}

	function findOkButton(wrapper) {
		return wrapper.findComponent(NcDialog).props('buttons').find((button) => button.variant === 'primary')
	}

	function findCancelButton(wrapper) {
		return wrapper.findComponent(NcDialog).props('buttons').find((button) => button.variant !== 'primary')
	}

	function enteredAddress(wrapper) {
		return wrapper.findComponent(NcTextField).props('modelValue')
	}

	it('submits the dialog form with Ok, so Enter adds the address', async () => {
		const wrapper = await mountWithAddress('example.test')

		expect(wrapper.findComponent(NcDialog).props('isForm')).toBe(true)
		expect(findOkButton(wrapper).type).toBe('submit')
		expect(findCancelButton(wrapper).type).not.toBe('submit')
	})

	it('translates the button labels', async () => {
		await mountWithAddress('example.test')

		expect(t).toHaveBeenCalledWith('mail', 'Cancel')
		expect(t).toHaveBeenCalledWith('mail', 'Ok')
	})

	it.each([
		[true, ''],
		[true, '   '],
		[true, '@'],
		[true, ' @ '],
		[false, 'example.test'],
	])('sets Ok disabled to %s for "%s"', async (disabled, address) => {
		const wrapper = await mountWithAddress(address)

		expect(findOkButton(wrapper).disabled).toBe(disabled)
	})

	it.each([
		[' example.test ', 'example.test', 'domain'],
		['@example.test', 'example.test', 'domain'],
		[' @ example.test', 'example.test', 'domain'],
		[' alice@example.test ', 'alice@example.test', 'individual'],
	])('adds "%s" as "%s" of type %s', async (input, address, type) => {
		store.addInternalAddress.mockResolvedValue()
		const wrapper = await mountWithAddress(input)

		await findOkButton(wrapper).callback()

		expect(store.addInternalAddress).toHaveBeenCalledWith({ address, type })
	})

	it('keeps the Ok button busy until the internal address is added', async () => {
		let finishAdding
		store.addInternalAddress.mockReturnValue(new Promise((resolve) => {
			finishAdding = resolve
		}))
		const wrapper = await mountWithAddress('example.test')

		const result = findOkButton(wrapper).callback()
		await flushPromises()

		expect(await Promise.race([result, 'still saving'])).toBe('still saving')
		finishAdding()
		expect(await result).not.toBe(false)
		await nextTick()
		expect(store.addInternalAddress).toHaveBeenCalledWith({ address: 'example.test', type: 'domain' })
		expect(enteredAddress(wrapper)).toBe('')
	})

	it('blocks cancelling and closing while the internal address is saved', async () => {
		let finishAdding
		store.addInternalAddress.mockReturnValue(new Promise((resolve) => {
			finishAdding = resolve
		}))
		const wrapper = await mountWithAddress('example.test')

		const result = findOkButton(wrapper).callback()
		await nextTick()

		expect(findCancelButton(wrapper).disabled).toBe(true)
		expect(wrapper.findComponent(NcDialog).props('noClose')).toBe(true)
		expect(wrapper.find('input').attributes('readonly')).toBeDefined()
		finishAdding()
		await result
		await nextTick()
		expect(findCancelButton(wrapper).disabled).toBe(false)
		expect(wrapper.findComponent(NcDialog).props('noClose')).toBe(false)
		expect(wrapper.find('input').attributes('readonly')).toBeUndefined()
	})

	it('keeps Ok enabled while saving, so it keeps the focus', async () => {
		store.addInternalAddress.mockReturnValue(new Promise(() => {}))
		const wrapper = await mountWithAddress('example.test')

		findOkButton(wrapper).callback()
		await nextTick()

		expect(findOkButton(wrapper).disabled).toBe(false)
	})

	it('keeps the dialog open if the internal address could not be added', async () => {
		store.addInternalAddress.mockRejectedValue(new Error('Request failed'))
		const wrapper = await mountWithAddress('example.test')

		const result = await findOkButton(wrapper).callback()
		await nextTick()

		expect(result).toBe(false)
		expect(showError).toHaveBeenCalledOnce()
		expect(enteredAddress(wrapper)).toBe('example.test')
		expect(findCancelButton(wrapper).disabled).toBe(false)
		expect(findOkButton(wrapper).disabled).toBe(false)
		expect(wrapper.findComponent(NcDialog).props('noClose')).toBe(false)
	})
})
