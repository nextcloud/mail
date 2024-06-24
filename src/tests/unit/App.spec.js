/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'

import App from '../../App.vue'
import Nextcloud from '../../mixins/Nextcloud.js'
import Vuex from 'vuex'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(Nextcloud)

jest.mock('../../service/AutoConfigService.js')

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
