/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import App from '../../App.vue'
import Nextcloud from '../../mixins/Nextcloud.js'
import useMainStore from '../../store/mainStore.js'

vi.mock('../../service/AutoConfigService.js')
vi.mock('../../init.js')
vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

describe('App', () => {
	let store
	let view

	beforeEach(() => {
		setActivePinia(createTestingPinia())

		store = useMainStore()
		store.isExpiredSession = false

		view = shallowMount(App, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$router: {
						replace: vi.fn(),
					},
				},
			},
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
