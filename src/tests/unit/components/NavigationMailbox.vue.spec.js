/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import NavigationMailbox from '../../../components/NavigationMailbox.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('NavigationMailbox', () => {
	const subMailboxes = []
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()

		store.getSubMailboxes = vi.fn().mockReturnValue([])
	})

	it('shows no counter', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					unread: 0,
				},
			},
		})

		expect(view.vm.showUnreadCounter).toBe(false)
		expect(view.vm.subCounter).toBe(0)
	})

	it('shows a counter', () => {
		subMailboxes.push({
			unread: 0,
		})
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					unread: 3,
				},
			},
		})

		expect(view.vm.showUnreadCounter).toBe(true)
		expect(view.vm.subCounter).toBe(0)
	})

	it('shows a counter for its children', () => {
		subMailboxes.push({
			unread: 5,
		})
		subMailboxes.push({
			unread: 2,
		})
		subMailboxes.push({
			unread: 0,
		})
		store.getSubMailboxes = vi.fn().mockReturnValue(subMailboxes)
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					unread: 0,
				},
			},
		})

		expect(view.vm.showUnreadCounter).toBe(true)
		expect(view.vm.subCounter).toBe(7)
	})

	it('allows rename with no ACLs set', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('allows rename with missing ACLs on parent', () => {
		store.getParentMailbox = vi.fn().mockReturnValue({
			myAcls: undefined,
		})
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('allows rename with x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('disallows rename without x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 's',
				},
			},
		})

		expect(view.vm.hasRenameAcl).toBe(false)
	})

	it('disallows rename without k ACL right on parent', () => {
		store.getParentMailbox = vi.fn().mockReturnValue({
			myAcls: 'x',
		})

		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasRenameAcl).toBe(false)
	})

	it('allows rename with k ACL right on parent', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('allows toggling seen flag without ACLs', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 's',
				},
			},
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})
	it('allows toggling submailbox action without ACLs', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
		})

		expect(view.vm.hasSubmailboxActionAcl).toBe(true)
	})
	it('disallows toggling submailbox action without k ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasSubmailboxActionAcl).toBe(false)
	})
	it('allows toggling submailbox action with k ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'k',
				},
			},
		})

		expect(view.vm.hasSubmailboxActionAcl).toBe(true)
	})

	it('allows toggling delete action without ACLs', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('disallows toggling delete action without x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 's',
				},
			},
		})

		expect(view.vm.hasDeleteAcl).toBe(false)
	})
	it('allows toggling delete action with x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})

	describe('when envelopes are dropped onto it', () => {
		const mountWithRoute = (route, push) => shallowMount(NavigationMailbox, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route: route,
					$router: { push },
				},
			},
			props: {
				account: {},
				mailbox: {
					databaseId: 7,
				},
			},
		})

		it('navigates to the folder root when the open message was moved', () => {
			const push = vi.fn()
			const view = mountWithRoute({ name: 'message', params: { mailboxId: '3', threadId: '42' } }, push)

			view.vm.onEnvelopesMoved({ mailboxId: 7, movedEnvelopes: [{ databaseId: 41 }, { databaseId: 42 }] })

			expect(push).toHaveBeenCalledWith({
				name: 'mailbox',
				params: { mailboxId: '3', filter: undefined },
			})
		})

		it('stays on the open message when other messages were moved', () => {
			const push = vi.fn()
			const view = mountWithRoute({ name: 'message', params: { mailboxId: '3', threadId: '42' } }, push)

			view.vm.onEnvelopesMoved({ mailboxId: 7, movedEnvelopes: [{ databaseId: 41 }] })

			expect(push).not.toHaveBeenCalled()
		})

		it('ignores moves into another mailbox', () => {
			const push = vi.fn()
			const view = mountWithRoute({ name: 'message', params: { mailboxId: '3', threadId: '42' } }, push)

			view.vm.onEnvelopesMoved({ mailboxId: 8, movedEnvelopes: [{ databaseId: 42 }] })

			expect(push).not.toHaveBeenCalled()
		})
	})
})
