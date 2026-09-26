/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MenuEnvelope from '../../../components/MenuEnvelope.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()
localVue.mixin(Nextcloud)

describe('MenuEnvelope', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	const mountMenu = (propsData = {}) => shallowMount(MenuEnvelope, {
		localVue,
		propsData: {
			envelope: {
				databaseId: 123,
				accountId: 1,
				flags: { flagged: false, seen: true },
				subject: 'Subject',
			},
			mailbox: { accountId: 1 },
			...propsData,
		},
		computed: {
			account: () => ({ snoozeMailboxId: null }),
			hasWriteAcl: () => false,
			hasDeleteAcl: () => false,
			tasksEnabled: () => false,
			isSnoozeDisabled: () => true,
			isSnoozedMailbox: () => false,
			isTranslationEnabled: () => false,
			isSieveEnabled: () => false,
		},
		data: () => ({ localMoreActionsOpen: true }),
	})

	it('does not offer the textual version without a plain body', () => {
		const view = mountMenu()

		expect(view.text()).not.toContain('View textual version')
	})

	it('offers the textual version and emits the toggle event', async () => {
		const view = mountMenu({ hasPlainBody: true })
		const action = view.findAllComponents({ name: 'NcActionButton' })
			.wrappers.find((button) => button.text().includes('View textual version'))

		action.vm.$emit('click', { preventDefault: vi.fn() })
		await view.vm.$nextTick()

		expect(view.emitted('toggle-textual-version')).toHaveLength(1)
	})

	it('offers the HTML version while showing text', () => {
		const view = mountMenu({
			hasPlainBody: true,
			showingTextualVersion: true,
		})

		expect(view.text()).toContain('View HTML version')
	})
})
