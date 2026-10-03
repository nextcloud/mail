/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { setActivePinia } from 'pinia'
import useMainStore from '../../../store/mainStore.js'

describe('Pinia main store getters', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia({ stubActions: false }))
		store = useMainStore()
		store.$patch({
			isExpiredSession: false,
			accountList: [],
			accounts: {},
			mailboxes: {},
			envelopes: {},
			messages: {},
			tagList: [],
			tags: {},
			calendars: [],
		})
	})

	it('gets session expiry', () => {
		expect(store.isExpiredSession).toEqual(false)
	})
	it('gets all accounts', () => {
		store.accountList.push('13')
		store.accountsUnmapped[13] = {
			accountId: 13,
		}

		const accounts = store.accountsUnmapped

		expect(accounts['13']).toEqual({
			accountId: 13,
		})
	})
	it('gets a specific account', () => {
		store.accountList.push('13')
		store.accountsUnmapped[13] = {
			accountId: 13,
		}

		const accounts = store.getAccount(13)

		expect(accounts).toEqual({
			accountId: 13,
		})
	})
	it('returns an envelope\'s empty thread', () => {
		const thread = store.getEnvelopeThread(1)

		expect(thread.length).toEqual(0)
	})
	it('returns an envelope\'s thread', () => {
		store.$patch({
			threads: {
				'1:root-1': {
					1: { accountId: 1, databaseId: 1, uid: 101, mailboxId: 13, threadRootId: 'root-1', dateInt: 1 },
					2: { accountId: 1, databaseId: 2, uid: 102, mailboxId: 13, threadRootId: 'root-1', dateInt: 2 },
					3: { accountId: 1, databaseId: 3, uid: 103, mailboxId: 13, threadRootId: 'root-1', dateInt: 3 },
				},
			},
			messageToThreadDictionnary: { 1: '1:root-1', 2: '1:root-1', 3: '1:root-1' },
			preferences: { 'layout-message-view': 'threaded' },
		})

		const thread = store.getEnvelopeThread(1)

		expect(thread.length).toEqual(3)
		expect(thread.map((e) => e.databaseId)).toEqual([1, 2, 3])
	})

	it('return envelopes by thread root id', () => {
		store.$patch({
			threads: {
				'1:123-456-789': {
					1: { accountId: 1, databaseId: 1, uid: 101, mailboxId: 13, threadRootId: '123-456-789', dateInt: 1 },
					2: { accountId: 1, databaseId: 2, uid: 102, mailboxId: 13, threadRootId: '123-456-789', dateInt: 2 },
				},
				'1:234-567-890': {
					3: { accountId: 1, databaseId: 3, uid: 103, mailboxId: 13, threadRootId: '234-567-890', dateInt: 3 },
					4: { accountId: 1, databaseId: 4, uid: 104, mailboxId: 13, threadRootId: '234-567-890', dateInt: 4 },
				},
			},
		})

		const envelopesA = store.getEnvelopesByThreadRootId({ accountId: 1, threadRootId: '123-456-789' })
		expect(envelopesA.length).toEqual(2)
		expect(envelopesA.map((e) => e.databaseId)).toEqual([1, 2])

		const envelopesB = store.getEnvelopesByThreadRootId({ accountId: 1, threadRootId: '345-678-901' })
		expect(envelopesB.length).toEqual(0)
	})

	it('returns undefined for an envelope that is not loaded', () => {
		store.$patch({ preferences: { 'layout-message-view': 'threaded' } })

		expect(store.getEnvelope(999)).toBeUndefined()
	})

	it('find mailbox by special role: inbox', () => {
		store.getMailboxes = vi.fn().mockReturnValue([
			{
				name: 'Test',
				specialRole: 0,
			},
			{
				name: 'INBOX',
				specialRole: 'inbox',
			},
			{
				name: 'Trash',
				specialRole: 'trash',
			},
		])

		const result = store.findMailboxBySpecialRole('100', 'inbox')

		expect(result).toEqual({
			name: 'INBOX',
			specialRole: 'inbox',
		})
	})

	it('find mailbox by special role: undefined', () => {
		store.getMailboxes = vi.fn().mockReturnValue([
			{
				name: 'Test',
				specialRole: 0,
			},
			{
				name: 'INBOX',
				specialRole: 'inbox',
			},
			{
				name: 'Trash',
				specialRole: 'trash',
			},
		])

		const result = store.findMailboxBySpecialRole('100', 'drafts')

		expect(result).toEqual(undefined)
	})
})
