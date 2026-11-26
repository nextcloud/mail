/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import MenuEnvelope from '../../../components/MenuEnvelope.vue'
import useMainStore from '../../../store/mainStore.js'

describe('MenuEnvelope', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useMainStore()
		store.accountsUnmapped[42] = { junkMailboxId: 10 }
		store.mailboxes[1] = { databaseId: 1, specialRole: 'inbox' }
		store.mailboxes[5] = { databaseId: 5, specialRole: 'junk' }
	})

	const isJunk = (envelope) => MenuEnvelope.computed.isJunk.call({ mainStore: store, envelope })

	it('treats an unflagged message in the junk mailbox as junk', () => {
		const envelope = { accountId: 42, mailboxId: 5, flags: { $junk: false } }

		expect(isJunk(envelope)).toBe(true)
	})

	it('does not treat an unflagged message in the inbox as junk', () => {
		const envelope = { accountId: 42, mailboxId: 1, flags: { $junk: false } }

		expect(isJunk(envelope)).toBe(false)
	})

	it('treats a flagged message in the inbox as junk', () => {
		const envelope = { accountId: 42, mailboxId: 1, flags: { $junk: true } }

		expect(isJunk(envelope)).toBe(true)
	})
})
