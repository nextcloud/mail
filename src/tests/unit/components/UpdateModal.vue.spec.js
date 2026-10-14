/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import UpdateModal from '../../../components/mailFilter/UpdateModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { MailFilterConditionField, MailFilterConditionOperator, MailFilterOperator } from '../../../models/mailFilter.ts'

function mountWithTests(tests, operator = MailFilterOperator.All) {
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
				operator,
				tests,
				actions: [{ id: 2, type: 'fileinto', mailbox: 'INBOX' }],
				priority: 10,
			},
		},
	})
}

describe('UpdateModal', () => {
	it('emits updateFilter for a filter with a condition', () => {
		const view = mountWithTests([{ id: 3, field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: ['Hello'] }])

		view.vm.updateFilter()

		expect(view.vm.hasTests).toBe(true)
		expect(view.emitted('updateFilter')).toHaveLength(1)
	})

	it('emits a copy that later edits do not change', () => {
		const view = mountWithTests([{ id: 3, field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: ['Hello'] }])

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
		const test = { id: 3, field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: ['Hello'] }
		const view = mountWithTests([test])

		view.vm.deleteTest(test)
		view.vm.updateFilter()

		expect(view.vm.hasTests).toBe(false)
		expect(view.emitted('updateFilter')).toBeUndefined()
	})

	it('does not emit updateFilter for a condition without values', () => {
		const view = mountWithTests([{ id: 3, field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: [] }])

		view.vm.updateFilter()

		expect(view.vm.isValid).toBe(false)
		expect(view.emitted('updateFilter')).toBeUndefined()
	})

	it('hides the validation error until the filter is submitted', () => {
		const view = mountWithTests([])

		expect(view.findComponent(NcNoteCard).exists()).toBe(false)
	})

	it('shows the validation error after an invalid submit', async () => {
		const view = mountWithTests([])

		view.vm.updateFilter()
		await view.vm.$nextTick()

		expect(view.findComponent(NcNoteCard).exists()).toBe(true)
	})

	it('emits a filter for all incoming messages without conditions', () => {
		const view = mountWithTests([{ id: 3, field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: ['Hello'] }])

		view.vm.updateOperator(MailFilterOperator.Always)
		view.vm.updateFilter()

		const [emitted] = view.emitted('updateFilter')[0]
		expect(emitted.operator).toBe(MailFilterOperator.Always)
		expect(emitted.tests).toEqual([])
	})

	it('saves a filter for all incoming messages that has no conditions', () => {
		const view = mountWithTests([], MailFilterOperator.Always)

		view.vm.updateFilter()

		expect(view.vm.isValid).toBe(true)
		expect(view.emitted('updateFilter')).toHaveLength(1)
	})

	it('restores the conditions when switching back from all incoming messages', () => {
		const test = { id: 3, field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: ['Hello'] }
		const view = mountWithTests([test])

		view.vm.updateOperator(MailFilterOperator.Always)
		view.vm.updateOperator(MailFilterOperator.Any)
		view.vm.updateFilter()

		const [emitted] = view.emitted('updateFilter')[0]
		expect(emitted.tests).toEqual([test])
	})
})
