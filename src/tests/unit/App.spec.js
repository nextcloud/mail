/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import App from '../../App.vue'
import Nextcloud from '../../mixins/Nextcloud.js'
import useMainStore from '../../store/mainStore.js'

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

vi.mock('../../service/AutoConfigService.js')

describe('App', () => {
	let store
	let view

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.isExpiredSession = false

		view = shallowMount(App, {
			store,
			localVue,
		})
	})

	it('handles session expiry', async () => {
		// Stub and prevent the actual reload
		view.vm.reload = vi.fn()

		expect(view.vm.isExpiredSession).toBe(false)
		store.isExpiredSession = true
		expect(view.vm.isExpiredSession).toBe(true)
	})
})
