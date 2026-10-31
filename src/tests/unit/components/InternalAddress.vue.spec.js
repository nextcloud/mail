/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { createTestingPinia } from '@pinia/testing'
import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { nextTick } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
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
		wrapper.findComponent(NcButton).vm.$emit('click')
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
		expect(wrapper.findComponent(NcTextField).props('error')).toBe(false)
	})

	describe('when the server rejects the address', () => {
		const validationError = () => Object.assign(new Error('Request failed with status code 422'), {
			response: { status: 422 },
		})

		async function addRejectedAddress() {
			store.addInternalAddress.mockRejectedValue(validationError())
			const wrapper = await mountWithAddress('not valid')
			const result = await findOkButton(wrapper).callback()
			await nextTick()
			return { wrapper, result }
		}

		it('shows the error at the text field instead of a toast', async () => {
			const { wrapper, result } = await addRejectedAddress()

			expect(result).toBe(false)
			expect(showError).not.toHaveBeenCalled()
			expect(t).toHaveBeenCalledWith('mail', 'Enter a valid email address or domain')
			expect(wrapper.findComponent(NcTextField).props('error')).toBe(true)
			expect(wrapper.findComponent(NcTextField).props('helperText')).not.toBe('')
			expect(enteredAddress(wrapper)).toBe('not valid')
			expect(document.activeElement).toBe(wrapper.find('input').element)
		})

		it('moves the focus to the field once the error is rendered', async () => {
			store.addInternalAddress.mockRejectedValue(validationError())
			const wrapper = await mountWithAddress('not valid')
			const input = wrapper.find('input').element
			let describedByOnFocus = null
			input.addEventListener('focus', () => {
				describedByOnFocus = input.getAttribute('aria-describedby')
			})

			await findOkButton(wrapper).callback()

			expect(document.activeElement).toBe(input)
			expect(describedByOnFocus).toContain('helper-text')
		})

		it('hides the previous error while the address is sent again', async () => {
			const { wrapper } = await addRejectedAddress()
			store.addInternalAddress.mockReturnValue(new Promise(() => {}))

			findOkButton(wrapper).callback()
			await nextTick()

			expect(wrapper.findComponent(NcTextField).props('error')).toBe(false)
		})

		it('ignores a rejection that arrives after the dialog is gone', async () => {
			let rejectAdding
			store.addInternalAddress.mockReturnValue(new Promise((resolve, reject) => {
				rejectAdding = reject
			}))
			const wrapper = await mountWithAddress('not valid')
			const result = findOkButton(wrapper).callback()

			wrapper.unmount()
			rejectAdding(validationError())

			expect(await result).toBe(false)
		})

		it('clears the error when the address is edited', async () => {
			const { wrapper } = await addRejectedAddress()

			await wrapper.findComponent(NcTextField).setValue('example.test')

			expect(wrapper.findComponent(NcTextField).props('error')).toBe(false)
			expect(wrapper.findComponent(NcTextField).props('helperText')).toBe('')
		})

		it('resets the address and the error when the dialog is closed', async () => {
			const { wrapper } = await addRejectedAddress()

			findCancelButton(wrapper).callback()
			await nextTick()

			expect(enteredAddress(wrapper)).toBe('')
			expect(wrapper.findComponent(NcTextField).props('error')).toBe(false)
		})
	})
})
