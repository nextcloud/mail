/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import AccountSettings from '../../../components/AccountSettings.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

vi.mock('../../../components/SieveDisabledHint.vue', () => ({
	default: { name: 'SieveDisabledHint', render: () => null },
}))

describe('AccountSettings', () => {
	const mount = (account) => shallowMount(AccountSettings, {
		props: {
			account: {
				id: 1,
				accountId: 1,
				name: 'Tom Turbo',
				emailAddress: 'tom@tom.turbo',
				...account,
			},
		},
		global: {
			mixins: [Nextcloud],
			plugins: [createTestingPinia()],
			renderStubDefaultSlot: true,
		},
	})

	it.each(['imap', 'jmap'])('shows the mail server settings for %s accounts', (protocol) => {
		const view = mount({ protocol })

		expect(view.findComponent({ name: 'AccountForm' }).exists()).toBe(true)
	})

	it('hides the mail server settings for provisioned accounts', () => {
		const view = mount({ protocol: 'jmap', provisioningId: 3 })

		expect(view.findComponent({ name: 'AccountForm' }).exists()).toBe(false)
	})
})
