/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import SearchMessages from '../../../components/SearchMessages.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

describe('SearchMessages', () => {
	let store
	let views

	const mountView = () => {
		const view = mount(SearchMessages, {
			localVue,
			propsData: {
				mailbox: { databaseId: 5 },
				accountId: 1,
			},
			stubs: {
				NcSelect: true,
				NcDateTimePickerNative: true,
				NcCheckboxRadioSwitch: true,
				NcChip: true,
			},
		})
		views.push(view)
		return view
	}

	const typeQuery = async (view, query) => {
		view.vm.query = query
		await view.vm.$nextTick()
		await vi.runAllTimersAsync()
	}

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

	beforeEach(() => {
		vi.useFakeTimers()
		views = []
		setActivePinia(createPinia())
		store = useMainStore()
		store.getAccount = vi.fn().mockReturnValue({ accountId: 1, emailAddress: 'user@example.com', searchBody: false })
		store.getPreference = vi.fn().mockReturnValue('false')
	})

	afterEach(() => {
		views.forEach((view) => view.destroy())
		document.querySelectorAll('.search-modal').forEach((element) => element.remove())
		vi.useRealTimers()
	})

	it('searches with the typed text', async () => {
		const view = mountView()

		await typeQuery(view, 'icloud.com')

		expect(view.emitted('search-changed')).toEqual([
			['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'],
		])
	})

	it('opens the modal without the typed text', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')

		await openModal(view)

		expect(dialog()).not.toBeNull()
		expect(document.querySelector('#subjectId').value).toBe('')
		expect(document.querySelector('#bodyId').value).toBe('')
		expect(view.vm.query).toBe('icloud.com')
	})

	it('keeps the typed search when the modal is dismissed with the close button', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		await openModal(view)

		await dismissWithClose()

		expect(dialog()).toBeNull()
		expect(view.emitted('search-changed')).toHaveLength(1)
		expect(view.emitted('search-changed').at(-1)).toEqual(['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'])
	})

	it('discards modal edits when the modal is dismissed', async () => {
		const view = mountView()
		await openModal(view)
		setInput('#subjectId', 'invoice')

		await dismissWithClose()
		await openModal(view)

		expect(view.emitted('search-changed')).toBeUndefined()
		expect(document.querySelector('#subjectId').value).toBe('')
	})

	it('keeps the typed search when Search is clicked without changes', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		await openModal(view)

		await clickDialogButton('Search')

		expect(view.emitted('search-changed')).toHaveLength(2)
		expect(view.emitted('search-changed').at(-1)).toEqual(['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'])
		expect(dialog()).toBeNull()
	})

	it('applies the advanced search once when Search is clicked with a subject', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		await openModal(view)
		setInput('#subjectId', 'invoice')

		await clickDialogButton('Search')

		expect(view.emitted('search-changed')).toHaveLength(2)
		expect(view.emitted('search-changed').at(-1)).toEqual(['subject:invoice mentions:false match:allof'])
		expect(view.vm.query).toBe('')
	})

	it('keeps an advanced field that equals the old typed text', async () => {
		const view = mountView()
		await typeQuery(view, 'invoice')
		await openModal(view)
		setInput('#subjectId', 'invoice')

		await clickDialogButton('Search')

		expect(view.emitted('search-changed').at(-1)).toEqual(['subject:invoice mentions:false match:allof'])
	})

	it('adds a new To address as a text criterion', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		await openModal(view)
		view.vm.addTag('a@b.c', 'to')

		await clickDialogButton('Search')

		expect(view.emitted('search-changed').at(-1)).toEqual(['to:a@b.c mentions:false match:allof'])
		expect(view.vm.query).toBe('')
	})

	it('clears the search once when Clear is clicked', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		await openModal(view)

		await clickDialogButton('Clear')

		expect(view.emitted('search-changed')).toHaveLength(2)
		expect(view.emitted('search-changed').at(-1)).toEqual(['mentions:false match:allof'])
		expect(view.vm.query).toBe('')
	})

	it('sends the pending typed search before the modal opens', async () => {
		const view = mountView()
		view.vm.query = 'icloud.com'
		await view.vm.$nextTick()

		await openModal(view)
		await dismissWithClose()

		expect(view.emitted('search-changed').some(([query]) => query === 'mentions:false match:anyof')).toBe(false)
		expect(view.emitted('search-changed').at(-1)).toEqual(['to:icloud.com from:icloud.com subject:icloud.com mentions:false match:anyof'])
	})

	it('keeps the typed search when the To quick filter is active', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		view.vm.toggleCurrentUser()
		await vi.runAllTimersAsync()
		await openModal(view)

		await clickDialogButton('Search')

		expect(view.emitted('search-changed').at(-1)[0]).toContain('from:icloud.com')
		expect(view.emitted('search-changed').at(-1)[0]).toContain('match:anyof')
		expect(view.vm.query).toBe('icloud.com')
	})

	it('keeps an applied search when the modal is reopened and dismissed', async () => {
		const view = mountView()
		await typeQuery(view, 'icloud.com')
		await openModal(view)
		setInput('#subjectId', 'invoice')
		await clickDialogButton('Search')

		await openModal(view)
		await dismissWithClose()

		expect(view.emitted('search-changed')).toHaveLength(2)
		expect(view.emitted('search-changed').at(-1)).toEqual(['subject:invoice mentions:false match:allof'])
		expect(view.vm.query).toBe('')
	})
})
