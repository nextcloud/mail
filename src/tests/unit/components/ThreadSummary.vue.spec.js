/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCapabilities } from '@nextcloud/capabilities'
import { shallowMount } from '@vue/test-utils'
import ThreadSummary from '../../../components/ThreadSummary.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

vi.mock('@nextcloud/capabilities', () => ({
	getCapabilities: vi.fn(),
}))

describe('ThreadSummary', () => {
	beforeEach(() => {
		global.t.mockClear()
	})

	const mountComponent = () => shallowMount(ThreadSummary, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			summary: 'Lorem ipsum',
			loading: false,
		},
	})

	it('uses the themed instance name as brand', () => {
		getCapabilities.mockReturnValue({ theming: { name: 'Acme Cloud' } })

		const view = mountComponent()

		view.vm.brand

		expect(global.t).toHaveBeenCalledWith('mail', '{name} Assistant', { name: 'Acme Cloud' })
	})

	it('falls back to Nextcloud without theming capabilities', () => {
		getCapabilities.mockReturnValue({})

		const view = mountComponent()

		view.vm.brand

		expect(global.t).toHaveBeenCalledWith('mail', '{name} Assistant', { name: 'Nextcloud' })
	})
})
