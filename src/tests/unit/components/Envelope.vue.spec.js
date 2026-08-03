/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Envelope from '../../../components/Envelope.vue'
import useMainStore from '../../../store/mainStore.js'

const $route = { params: { id: 1 } }

const data = {
	accountId: 123,
	from: [{ email: 'info@test.com' }],
	flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
}

const mount = (mailboxAcls, archiveAcls) => {
	const computed = {}
	if (archiveAcls !== undefined) {
		computed.archiveMailbox = () => ({ myAcls: archiveAcls })
	}
	return shallowMount({ extends: Envelope, computed }, {
		props: {
			data,
			mailbox: { specialRole: '', databaseId: '3', myAcls: mailboxAcls },
		},
		global: { mocks: { $route } },
	})
}

describe('Envelope', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.accountsUnmapped[123] = { sentMailboxId: '1' }
	})

	it('allows toggling seen flag without ACLs', () => {
		const view = mount(undefined)
		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = mount('x')
		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = mount('s')
		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('allows toggling archive action without ACLs', () => {
		const view = mount(undefined, undefined)
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has i ACLs for archiving', () => {
		const view = mount('te', 'i')
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has no ACLs for archiving', () => {
		const view = mount('te', undefined)
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has no acls and archive mailbox has i ACL for archiving', () => {
		const view = mount(undefined, 'i')
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('disallows toggling archive action without i ACL right', () => {
		const view = mount('x')
		expect(view.vm.hasArchiveAcl).toBe(false)
	})

	it('allows toggling delete action without ACLs', () => {
		const view = mount(undefined)
		expect(view.vm.hasDeleteAcl).toBe(true)
	})

	it('disallows toggling delete action without x ACL right', () => {
		const view = mount('s')
		expect(view.vm.hasDeleteAcl).toBe(false)
	})

	it('allows toggling delete action with te ACL right', () => {
		const view = shallowMount(Envelope, {
			props: {
				mailbox: { specialRole: '', databaseId: '3', sentMailboxId: '1' },
				data,
			},
			global: { mocks: { $route } },
		})
		expect(view.vm.hasDeleteAcl).toBe(true)
	})

	it('allows toggling favorite, important and spam action with w ACL right', () => {
		const view = mount('w')
		expect(view.vm.hasWriteAcl).toBe(true)
	})

	it('allows toggling favorite, important and spam action without w ACL right', () => {
		const view = mount('s')
		expect(view.vm.hasWriteAcl).toBe(false)
	})

	it('allows toggling favorite, important and spam action without ACL right', () => {
		const view = mount(undefined)
		expect(view.vm.hasWriteAcl).toBe(true)
	})
})
