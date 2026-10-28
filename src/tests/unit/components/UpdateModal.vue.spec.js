/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import UpdateModal from '../../../components/mailFilter/UpdateModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

function mountWithTests(tests) {
	return shallowMount(UpdateModal, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			account: { id: 1 },
			filter: {
				id: 1,
				name: 'Filter',
				enable: true,
				operator: 'allof',
				tests,
				actions: [{ id: 2, type: 'fileinto', mailbox: 'INBOX' }],
				priority: 10,
			},
		},
	})
}

describe('UpdateModal', () => {
	it('emits updateFilter for a filter with a condition', () => {
		const view = mountWithTests([{ id: 3, field: 'subject', operator: 'is', values: ['Hello'] }])

		view.vm.updateFilter()

		expect(view.vm.hasTests).toBe(true)
		expect(view.emitted('updateFilter')).toHaveLength(1)
	})

	it('emits a copy that later edits do not change', () => {
		const view = mountWithTests([{ id: 3, field: 'subject', operator: 'is', values: ['Hello'] }])

		view.vm.updateFilter()
		view.vm.clone.tests[0].values.push('World')
		view.vm.clone.actions[0].mailbox = 'Archive'

		const [emitted] = view.emitted('updateFilter')[0]
		expect(emitted.tests[0].values).toEqual(['Hello'])
		expect(emitted.actions[0].mailbox).toBe('INBOX')
	})

	it('does not emit updateFilter for a filter without conditions', () => {
		const view = mountWithTests([])

		view.vm.updateFilter()

		expect(view.vm.hasTests).toBe(false)
		expect(view.emitted('updateFilter')).toBeUndefined()
	})

	it('blocks saving after the last condition is deleted', () => {
		const test = { id: 3, field: 'subject', operator: 'is', values: ['Hello'] }
		const view = mountWithTests([test])

		view.vm.deleteTest(test)
		view.vm.updateFilter()

		expect(view.vm.hasTests).toBe(false)
		expect(view.emitted('updateFilter')).toBeUndefined()
	})
})
