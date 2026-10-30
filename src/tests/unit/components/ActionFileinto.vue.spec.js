/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MailboxInlinePicker from '../../../components/MailboxInlinePicker.vue'
import ActionFileinto from '../../../components/mailFilter/ActionFileinto.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { MailFilterActions } from '../../../models/mailFilter.ts'

describe('ActionFileinto', () => {
	let pinia

	beforeEach(() => {
		pinia = createPinia()
		setActivePinia(pinia)
	})

	it('requires a mailbox', () => {
		const view = shallowMount(ActionFileinto, {
			global: {
				mixins: [Nextcloud],
				plugins: [pinia],
			},
			props: {
				action: { id: 1, type: MailFilterActions.FileInto },
				account: { id: 1 },
			},
		})

		const picker = view.findComponent(MailboxInlinePicker)

		expect(picker.attributes('required')).toBe('true')
		expect(picker.props('modelValue')).toBeUndefined()
	})
})
