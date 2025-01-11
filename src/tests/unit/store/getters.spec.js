/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { setActivePinia } from 'pinia'
import { createTestingPinia } from '@pinia/testing'
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

		expect(accounts['13']).toEqual(
			{
				accountId: 13,
			},
		)
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
		store.envelopes[1] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
		}

		const thread = store.getEnvelopeThread(1)

		expect(thread.length).toEqual(0)
	})
	it('returns an envelope\'s empty thread', () => {
		store.envelopes[1] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
			thread: [
				1,
				2,
				3,
			],
		}
		store.envelopes[2] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
		}
		store.envelopes[3] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
		}

		const thread = store.getEnvelopeThread(1)

		expect(thread.length).toBeGreaterThanOrEqual(1)
		expect(thread).toEqual([
			{
				databaseId: 1,
				uid: 101,
				mailboxId: 13,
				thread: [
					1,
					2,
					3,
				],
			},
			{
				databaseId: 1,
				uid: 101,
				mailboxId: 13,
			},
			{
				databaseId: 1,
				uid: 101,
				mailboxId: 13,
			},
		])
	})

	it('return envelopes by thread root id', () => {
		store.envelopes[0] = {
			accountId: 1,
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
			threadRootId: '123-456-789',
		}
		store.envelopes[1] = {
			accountId: 1,
			databaseId: 2,
			uid: 102,
			mailboxId: 13,
			threadRootId: '123-456-789',
		}
		store.envelopes[2] = {
			accountId: 1,
			databaseId: 3,
			uid: 103,
			mailboxId: 13,
			threadRootId: '234-567-890',
		}
		store.envelopes[3] = {
			accountId: 1,
			databaseId: 4,
			uid: 104,
			mailboxId: 13,
			threadRootId: '234-567-890',
		}
		store.envelopes[4] = {
			accountId: 2,
			databaseId: 5,
			uid: 105,
			mailboxId: 23,
			threadRootId: '123-456-789',
		}

		const envelopesA = store.getEnvelopesByThreadRootId(1, '123-456-789')
		expect(envelopesA.length).toEqual(2)
		expect(envelopesA).toEqual([
			{
				accountId: 1,
				databaseId: 1,
				uid: 101,
				mailboxId: 13,
				threadRootId: '123-456-789',
			},
			{
				accountId: 1,
				databaseId: 2,
				uid: 102,
				mailboxId: 13,
				threadRootId: '123-456-789',
			},
		])

		const envelopesB = store.getEnvelopesByThreadRootId('345-678-901')
		expect(envelopesB.length).toEqual(0)
	})

	it('find mailbox by special role: inbox', () => {
		store.getMailboxes = jest.fn().mockReturnValue([
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
		store.getMailboxes = jest.fn().mockReturnValue([
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
