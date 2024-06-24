/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { curry, mapObjIndexed } from 'ramda'

import { getters } from '../../../store/getters'

const bindGetterToState = curry((getters, state, num, key) => getters[key](state, getters))

describe('Vuex store getters', () => {
	let state
	let bindGetters

	beforeEach(() => {
		state = {
			isExpiredSession: false,
			accountList: [],
			accounts: {},
			mailboxes: {},
			envelopes: {},
			messages: {},
			tagList: [],
			tags: {},
			calendars: [],
		}
		bindGetters = () => mapObjIndexed(bindGetterToState(getters, state), getters)
	})

	it('gets session expiry', () => {
		const getters = bindGetters()

		expect(getters.isExpiredSession).toEqual(false)
	})
	it('gets all accounts', () => {
		state.accountList.push('13')
		state.accounts[13] = {
			accountId: 13,
		}
		const getters = bindGetters()

		const accounts = getters.accounts

		expect(accounts).toEqual([
			{
				accountId: 13,
			},
		])
	})
	it('gets a specific account', () => {
		state.accountList.push('13')
		state.accounts[13] = {
			accountId: 13,
		}
		const getters = bindGetters()

		const accounts = getters.getAccount(13)

		expect(accounts).toEqual({
			accountId: 13,
		})
	})
	it('returns an envelope\'s empty thread', () => {
		state.envelopes[1] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
		}
		const getters = bindGetters()

		const thread = getters.getEnvelopeThread(1)

		expect(thread.length).toEqual(0)
	})
	it('returns an envelope\'s empty thread', () => {
		state.envelopes[1] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
			thread: [
				1,
				2,
				3,
			],
		}
		state.envelopes[2] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
		}
		state.envelopes[3] = {
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
		}
		const getters = bindGetters()

		const thread = getters.getEnvelopeThread(1)

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
		state.envelopes[0] = {
			accountId: 1,
			databaseId: 1,
			uid: 101,
			mailboxId: 13,
			threadRootId: '123-456-789',
		}
		state.envelopes[1] = {
			accountId: 1,
			databaseId: 2,
			uid: 102,
			mailboxId: 13,
			threadRootId: '123-456-789',
		}
		state.envelopes[2] = {
			accountId: 1,
			databaseId: 3,
			uid: 103,
			mailboxId: 13,
			threadRootId: '234-567-890',
		}
		state.envelopes[3] = {
			accountId: 1,
			databaseId: 4,
			uid: 104,
			mailboxId: 13,
			threadRootId: '234-567-890',
		}
		state.envelopes[4] = {
			accountId: 2,
			databaseId: 5,
			uid: 105,
			mailboxId: 23,
			threadRootId: '123-456-789',
		}
		const getters = bindGetters()

		const envelopesA = getters.getEnvelopesByThreadRootId(1, '123-456-789')
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

		const envelopesB = getters.getEnvelopesByThreadRootId('345-678-901')
		expect(envelopesB.length).toEqual(0)
	})

	it('find mailbox by special role: inbox', () => {
		const mockedGetters = {
			getMailboxes: () => [
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
			],
		}

		const result = getters.findMailboxBySpecialRole(state, mockedGetters)('100', 'inbox')

		expect(result).toEqual({
			name: 'INBOX',
			specialRole: 'inbox',
		});
	})

	it('find mailbox by special role: undefined', () => {
		const mockedGetters = {
			getMailboxes: () => [
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
			],
		}

		const result = getters.findMailboxBySpecialRole(state, mockedGetters)('100', 'drafts')

		expect(result).toEqual(undefined);
	})
})
