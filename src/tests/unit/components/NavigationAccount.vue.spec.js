/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { createTestingPinia } from '@pinia/testing'
import { RouterLinkStub, shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import NavigationAccount from '../../../components/NavigationAccount.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

describe('NavigationAccount', () => {
	let store

	const mountAccount = (account, props = {}, route = { params: {} }) => shallowMount(NavigationAccount, {
		props: {
			account: {
				id: 13,
				emailAddress: 'jane@example.com',
				quotaPercentage: null,
				folded: false,
				...account,
			},
			...props,
		},
		global: {
			mixins: [Nextcloud],
			stubs: {
				RouterLink: RouterLinkStub,
			},
			mocks: {
				$route: route,
			},
		},
	})

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()

		store.getMailboxes = vi.fn().mockReturnValue([
			{ databaseId: 1, specialRole: 'inbox', unread: 4 },
			{ databaseId: 2, specialRole: 'sent', unread: 7 },
		])
		store.toggleAccountFolded = vi.fn().mockResolvedValue(undefined)
	})

	it('marks an unfolded account as expanded', () => {
		const view = mountAccount({ folded: false })

		const toggle = view.find('.navigation-account-header__toggle')
		expect(toggle.exists()).toBe(true)
		expect(toggle.attributes('aria-expanded')).toBe('true')
		expect(view.find('.navigation-account-header__unread').exists()).toBe(false)
	})

	it('marks a folded account as collapsed and shows the inbox unread count', () => {
		const view = mountAccount({ folded: true })

		expect(view.find('.navigation-account-header__toggle').attributes('aria-expanded')).toBe('false')
		expect(view.vm.inboxUnread).toBe(4)
		expect(view.find('.navigation-account-header__unread').exists()).toBe(true)
	})

	it('describes the unread count for screen readers', () => {
		const view = mountAccount({ folded: true })

		expect(view.find('.navigation-account-header__unread').attributes('aria-hidden')).toBe('true')
		expect(view.find('.hidden-visually').text()).toBe(view.vm.unreadLabel)
	})

	it('hides the unread count of a folded account without unread messages', () => {
		store.getMailboxes = vi.fn().mockReturnValue([
			{ databaseId: 1, specialRole: 'inbox', unread: 0 },
		])
		const view = mountAccount({ folded: true })

		expect(view.vm.inboxUnread).toBe(0)
		expect(view.find('.navigation-account-header__unread').exists()).toBe(false)
	})

	it('toggles the folded state through the store', async () => {
		const view = mountAccount({ folded: false })

		await view.vm.toggleFolded()

		expect(store.toggleAccountFolded).toHaveBeenCalledWith(13)
	})

	it('disables the toggle while the folded state is being saved', async () => {
		let finishSaving
		store.toggleAccountFolded = vi.fn(() => new Promise((resolve) => {
			finishSaving = resolve
		}))
		const view = mountAccount({ folded: false })

		const saving = view.vm.toggleFolded()
		await view.vm.$nextTick()

		expect(view.findComponent('.navigation-account-header__toggle').props('disabled')).toBe(true)

		finishSaving()
		await saving
		await view.vm.$nextTick()

		expect(view.findComponent('.navigation-account-header__toggle').props('disabled')).toBe(false)
	})

	it('shows an error when the folded state cannot be saved', async () => {
		store.toggleAccountFolded = vi.fn().mockRejectedValue(new Error('network down'))
		const view = mountAccount({ folded: false })

		await view.vm.toggleFolded()

		expect(showError).toHaveBeenCalled()
		expect(view.vm.savingFolded).toBe(false)
	})

	it('links the account name to its inbox', () => {
		const view = mountAccount({ folded: true })

		const link = view.findComponent(RouterLinkStub)
		expect(link.exists()).toBe(true)
		expect(link.props('to')).toEqual({ name: 'mailbox', params: { mailboxId: 1 } })
		expect(link.text()).toBe('jane@example.com')
	})

	it('shows the account name without a link when the inbox is not loaded', () => {
		store.getMailboxes = vi.fn().mockReturnValue([])
		const view = mountAccount({ folded: false })

		expect(view.findComponent(RouterLinkStub).exists()).toBe(false)
		expect(view.find('.navigation-account-header__name').text()).toBe('jane@example.com')
	})

	it('does not link the name of a disabled account', () => {
		const view = mountAccount({ folded: false }, { isDisabled: true })

		expect(view.findComponent(RouterLinkStub).exists()).toBe(false)
	})

	it('highlights a folded account while its inbox is open', () => {
		const view = mountAccount({ folded: true }, {}, { params: { mailboxId: '1' } })

		expect(view.find('.navigation-account-header').classes()).toContain('navigation-account-header--active')
	})

	it('does not highlight a folded account while another folder is open', () => {
		const view = mountAccount({ folded: true }, {}, { params: { mailboxId: '2' } })

		expect(view.find('.navigation-account-header').classes()).not.toContain('navigation-account-header--active')
	})

	it('does not highlight a folded account while its favorites are open', () => {
		const view = mountAccount({ folded: true }, {}, { params: { mailboxId: '1', filter: 'starred' } })

		expect(view.find('.navigation-account-header').classes()).not.toContain('navigation-account-header--active')
	})

	it('does not highlight an unfolded account because its inbox entry is visible', () => {
		const view = mountAccount({ folded: false }, {}, { params: { mailboxId: '1' } })

		expect(view.find('.navigation-account-header').classes()).not.toContain('navigation-account-header--active')
	})

	it('does not offer folding for a disabled account', () => {
		const view = mountAccount({ folded: false }, { isDisabled: true })

		expect(view.find('.navigation-account-header__toggle').exists()).toBe(false)
	})
})
