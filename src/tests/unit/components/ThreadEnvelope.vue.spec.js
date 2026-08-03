/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ThreadEnvelope from '../../../components/ThreadEnvelope.vue'

const envelope = {
	accountId: 123,
	from: [{ email: 'info@test.com' }],
	flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
	subject: '',
	dateInt: 1692200926180,
}

const baseProps = {
	account: {},
	mailbox: { specialRole: '' },
	envelope,
	threadSubject: '',
}

const mountWith = (mailboxAcls, archiveAcls) => {
	const computed = {
		mailbox() { return { myAcls: mailboxAcls } },
	}
	if (archiveAcls !== undefined) {
		computed.archiveMailbox = () => ({ myAcls: archiveAcls })
	}
	return shallowMount({ extends: ThreadEnvelope, computed }, { props: baseProps })
}

describe('ThreadEnvelope', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('allows toggling seen flag without ACLs', () => {
		const view = mountWith(undefined)
		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = mountWith('x')
		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = mountWith('s')
		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('allows toggling archive action without ACLs', () => {
		const view = mountWith(undefined, undefined)
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has i ACLs for archiving', () => {
		const view = mountWith('te', 'i')
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has no ACLs for archiving', () => {
		const view = mountWith('te', undefined)
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has no acls and archive mailbox has i ACL for archiving', () => {
		const view = mountWith(undefined, 'i')
		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('disallows toggling archive action without w ACL right', () => {
		const view = mountWith('x')
		expect(view.vm.hasArchiveAcl).toBe(false)
	})

	it('allows toggling delete action without ACLs', () => {
		const view = mountWith(undefined)
		expect(view.vm.hasDeleteAcl).toBe(true)
	})

	it('disallows toggling delete action without x ACL right', () => {
		const view = mountWith('s')
		expect(view.vm.hasDeleteAcl).toBe(false)
	})

	it('allows toggling delete action with te ACL right', () => {
		const view = mountWith('te')
		expect(view.vm.hasDeleteAcl).toBe(true)
	})

	it('allows toggling favorite, important and spam action with w ACL right', () => {
		const view = mountWith('w')
		expect(view.vm.hasWriteAcl).toBe(true)
	})

	it('allows toggling favorite, important and spam action without w ACL right', () => {
		const view = mountWith('s')
		expect(view.vm.hasWriteAcl).toBe(false)
	})

	it('allows toggling favorite, important and spam action without ACL right', () => {
		const view = mountWith(undefined)
		expect(view.vm.hasWriteAcl).toBe(true)
	})
})
