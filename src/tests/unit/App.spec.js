/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import App from '../../App.vue'
import useMainStore from '../../store/mainStore.js'

vi.mock('../../service/AutoConfigService.js')

describe('App', () => {
	let store
	let view

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
		store.isExpiredSession = false

		view = shallowMount(App)
	})

	it('handles session expiry', async () => {
		// Stub and prevent the actual reload
		view.vm.reload = vi.fn()

		expect(view.vm.isExpiredSession).toBe(false)
		store.isExpiredSession = true
		expect(view.vm.isExpiredSession).toBe(true)
	})
})
