/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import ActionAddSystemFlag from '../../../components/mailFilter/ActionAddSystemFlag.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { MailFilterActions, MailFilterSystemFlag } from '../../../models/mailFilter.ts'

function mountWithAction(action) {
	return shallowMount(ActionAddSystemFlag, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			action,
			account: { id: 1 },
		},
	})
}

describe('ActionAddSystemFlag', () => {
	it('passes null for a missing flag so the required check applies', () => {
		const view = mountWithAction({ id: 1, type: MailFilterActions.AddSystemFlag })

		expect(view.findComponent(NcSelect).props('modelValue')).toBeNull()
	})

	it('passes the selected flag', () => {
		const view = mountWithAction({ id: 1, type: MailFilterActions.AddSystemFlag, flag: MailFilterSystemFlag.Seen })

		expect(view.findComponent(NcSelect).props('modelValue')).toBe(MailFilterSystemFlag.Seen)
	})
})
