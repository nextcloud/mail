/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import { PiniaVuePlugin, setActivePinia } from 'pinia'
import MailboxInlinePicker from '../../../components/MailboxInlinePicker.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()
localVue.use(PiniaVuePlugin)
localVue.mixin(Nextcloud)

describe('MailboxInlinePicker', () => {
	let store

	const mount = () => shallowMount(MailboxInlinePicker, {
		propsData: {
			account: { accountId: 1 },
		},
		localVue,
	})

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
	})

	it('flattens nested mailboxes into indented options', () => {
		store.getMailboxes = vi.fn().mockReturnValue([
			{ databaseId: 1, displayName: 'INBOX' },
			{ databaseId: 4, displayName: 'Sent' },
		])
		store.getSubMailboxes = vi.fn().mockImplementation((id) => {
			switch (id) {
				case 1:
					return [{ databaseId: 2, displayName: 'Archive' }]
				case 2:
					return [{ databaseId: 3, displayName: '2020' }]
				default:
					return []
			}
		})

		const view = mount()

		expect(view.vm.mailboxes).toEqual([
			{ id: 1, label: 'INBOX', depth: 0 },
			{ id: 2, label: 'Archive', depth: 1 },
			{ id: 3, label: '2020', depth: 2 },
			{ id: 4, label: 'Sent', depth: 0 },
		])
	})

	it('skips mailboxes the user may not insert into', () => {
		store.getMailboxes = vi.fn().mockReturnValue([
			{ databaseId: 1, displayName: 'INBOX' },
			{ databaseId: 2, displayName: 'Shared', myAcls: 'lr' },
		])
		store.getSubMailboxes = vi.fn().mockReturnValue([])

		const view = mount()

		expect(view.vm.mailboxes).toEqual([
			{ id: 1, label: 'INBOX', depth: 0 },
		])
	})
})
