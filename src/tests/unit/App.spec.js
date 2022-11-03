/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import { createLocalVue, shallowMount } from '@vue/test-utils'

import App from '../../App'
import Nextcloud from '../../mixins/Nextcloud'
import Vuex from 'vuex'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(Nextcloud)

jest.mock('../../service/AutoConfigService')

describe('App', () => {

	let state
	let getters
	let store
	let view

	beforeEach(() => {
		state = { isExpiredSession: false };
		getters = {
			isExpiredSession: (state) => state.isExpiredSession,
		}
		store = new Vuex.Store({
			getters,
			state,
		})

		view = shallowMount(App, {
			store,
			localVue,
		})
	})

	it('handles session expiry', async() => {
		// Stub and prevent the actual reload
		view.vm.reload = jest.fn()

		expect(view.vm.isExpiredSession).toBe(false)
		state.isExpiredSession = true
		expect(view.vm.isExpiredSession).toBe(true)
	})

})
