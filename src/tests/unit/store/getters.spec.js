/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
			accountList: [],
			accounts: {},
			folders: {},
			envelopes: {},
			messages: {},
		}
		bindGetters = () => mapObjIndexed(bindGetterToState(getters, state), getters)
	})

	it('gets all accounts', () => {
		state.accountList.push('13')
		state.accounts[13] = {
			accountId: 13,
		}
		const getters = bindGetters()

		const accounts = getters.accounts

		expect(accounts).to.deep.equal([
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

		expect(accounts).to.deep.equal({
			accountId: 13,
		})
	})
	it('gets account folders', () => {})
})
