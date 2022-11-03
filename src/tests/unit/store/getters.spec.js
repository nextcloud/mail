/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
})
