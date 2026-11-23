/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { mount as mountComponent, shallowMount } from '@vue/test-utils'
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

	describe('search modal', () => {
		let mounted = []

		const mountWithDialog = () => {
			const view = mountComponent(SearchMessages, {
				props: {
					mailbox: { databaseId: 5 },
					accountId: 1,
				},
				global: {
					plugins: [pinia],
					mixins: [Nextcloud],
					stubs: {
						NcSelect: true,
						NcDateTimePickerNative: true,
						NcCheckboxRadioSwitch: true,
						NcChip: true,
					},
				},
			})
			mounted.push(view)
			return view
		}

		afterEach(() => {
			mounted.forEach((view) => view.unmount())
			mounted = []
		})

		const dialog = () => document.querySelector('.search-modal')

		const dialogButton = (label) => [...dialog().querySelectorAll('button')].find((button) => button.textContent.trim() === label)

		const openModal = async (view) => {
			await view.find('.search-messages--filter').trigger('click')
			await vi.runAllTimersAsync()
		}

		const clickDialogButton = async (label) => {
			dialogButton(label).click()
			await vi.runAllTimersAsync()
		}

		const dismissWithClose = async () => {
			dialog().querySelector('button[aria-label="Close"]').click()
			await vi.runAllTimersAsync()
		}

		const setInput = (selector, value) => {
			const input = dialog().querySelector(selector)
			input.value = value
			input.dispatchEvent(new Event('input'))
		}

		it('opens the modal without the typed text', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()

			await openModal(view)

			expect(dialog()).not.toBeNull()
			expect(document.querySelector('#subjectId').value).toBe('')
			expect(document.querySelector('#bodyId').value).toBe('')
			expect(view.find('.search-messages--input').element.value).toBe('icloud.com')
		})

		it('keeps the typed search when the modal is dismissed with the close button', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()
			await openModal(view)

			await dismissWithClose()

			expect(dialog()).toBeNull()
			expect(view.emitted('searchChanged')).toHaveLength(1)
			expect(view.emitted('searchChanged').at(-1)).toEqual(['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'])
		})

		it('discards modal edits when the modal is dismissed', async () => {
			const view = mountWithDialog()
			await openModal(view)
			setInput('#subjectId', 'invoice')

			await dismissWithClose()
			await openModal(view)

			expect(view.emitted('searchChanged')).toBeUndefined()
			expect(document.querySelector('#subjectId').value).toBe('')
		})

		it('keeps the typed search when Search is clicked without changes', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()
			await openModal(view)

			await clickDialogButton('Search')

			expect(view.emitted('searchChanged')).toHaveLength(2)
			expect(view.emitted('searchChanged').at(-1)).toEqual(['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'])
			expect(dialog()).toBeNull()
		})

		it('applies the advanced search once when Search is clicked with a subject', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()
			await openModal(view)
			setInput('#subjectId', 'invoice')

			await clickDialogButton('Search')

			expect(view.emitted('searchChanged')).toHaveLength(2)
			expect(view.emitted('searchChanged').at(-1)).toEqual(['subject:invoice mentions:false match:allof'])
			expect(view.vm.query).toBe('')
			expect(view.find('.search-messages--input').element.value).toBe('')
		})

		it('keeps an advanced field that equals the old typed text', async () => {
			const view = mountWithDialog()
			view.vm.query = 'invoice'
			await vi.runAllTimersAsync()
			await openModal(view)
			setInput('#subjectId', 'invoice')

			await clickDialogButton('Search')

			expect(view.emitted('searchChanged').at(-1)).toEqual(['subject:invoice mentions:false match:allof'])
		})

		it('clears the search once when Clear is clicked', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()
			await openModal(view)

			await clickDialogButton('Clear')

			expect(view.emitted('searchChanged')).toHaveLength(2)
			expect(view.emitted('searchChanged').at(-1)).toEqual(['mentions:false match:allof'])
			expect(view.vm.query).toBe('')
		})

		it('sends the pending typed search before the modal opens', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await view.vm.$nextTick()

			await openModal(view)
			await dismissWithClose()

			expect(view.emitted('searchChanged').every(([query]) => query !== 'mentions:false match:anyof')).toBe(true)
			expect(view.emitted('searchChanged').at(-1)).toEqual(['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'])
		})

		it('keeps the typed search when the To quick filter is active', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()
			view.vm.toggleCurrentUser()
			await vi.runAllTimersAsync()
			await openModal(view)

			await clickDialogButton('Search')

			expect(view.emitted('searchChanged').at(-1)[0]).toContain('from:icloud.com')
			expect(view.emitted('searchChanged').at(-1)[0]).toContain('match:anyof')
			expect(view.vm.query).toBe('icloud.com')
		})

		it('keeps an applied search when the modal is reopened and dismissed', async () => {
			const view = mountWithDialog()
			view.vm.query = 'icloud.com'
			await vi.runAllTimersAsync()
			await openModal(view)
			setInput('#subjectId', 'invoice')
			await clickDialogButton('Search')

			await openModal(view)
			await dismissWithClose()

			expect(view.emitted('searchChanged')).toHaveLength(2)
			expect(view.emitted('searchChanged').at(-1)).toEqual(['subject:invoice mentions:false match:allof'])
			expect(view.vm.query).toBe('')
		})
	})
})
