/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'
import Vuex from 'vuex'

import NavigationMailbox from '../../../components/NavigationMailbox.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('NavigationMailbox', () => {

	let actions
	let getters
	let store
	let parentMailbox = undefined
	let subMailboxes = []

	beforeEach(() => {
		actions = {}
		getters = {
			getSubMailboxes: () => () => subMailboxes,
			getParentMailbox: () => (id) => parentMailbox,
		}
		store = new Vuex.Store({
			actions,
			getters,
		})
	})

	it('shows no counter', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					unread: 0,
				},
			},
			store,
			localVue,
		})

		expect(view.vm.showUnreadCounter).toBe(false)
		expect(view.vm.subCounter).toBe(0)
	})

	it('shows a counter', () => {
		subMailboxes.push({
			unread: 0,
		})
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					unread: 3,
				},
			},
			store,
			localVue,
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
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					unread: 0,
				},
			},
			store,
			localVue,
		})

		expect(view.vm.showUnreadCounter).toBe(true)
		expect(view.vm.subCounter).toBe(7)
	})

	it('allows rename with no ACLs set', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('allows rename with missing ACLs on parent', () => {
		parentMailbox = {
			myAcls: undefined,
		}
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('allows rename with x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('disallows rename without x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 's',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasRenameAcl).toBe(false)
	})

	it('disallows rename without k ACL right on parent', () => {
		parentMailbox = {
			myAcls: 'x',
		}
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasRenameAcl).toBe(false)
	})

	it('allows rename with k ACL right on parent', () => {
		parentMailbox = {
			myAcls: 'k',
		}
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasRenameAcl).toBe(true)
	})

	it('allows toggling seen flag without ACLs', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 's',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})
	it('allows toggling submailbox action without ACLs', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSubmailboxActionAcl).toBe(true)
	})
	it('disallows toggling submailbox action without k ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSubmailboxActionAcl).toBe(false)
	})
	it('allows toggling submailbox action with k ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'k',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSubmailboxActionAcl).toBe(true)
	})


	it('allows toggling delete action without ACLs', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: undefined,
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('disallows toggling delete action without x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 's',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(false)
	})
	it('allows toggling delete action with x ACL right', () => {
		const view = shallowMount(NavigationMailbox, {
			propsData: {
				account: {},
				mailbox: {
					myAcls: 'x',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
})
