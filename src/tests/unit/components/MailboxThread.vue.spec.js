/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import MailboxThread from '../../../components/MailboxThread.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('MailboxThread', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
		store.getPreference = vi.fn().mockImplementation((key, fallback) => (key === 'sort-favorites' ? 'true' : fallback))
		store.getMailbox = vi.fn().mockReturnValue({ databaseId: 'unified' })
		store.getEnvelopes = vi.fn().mockReturnValue([])
	})

	it('opens the section info popovers on hover and keyboard focus', () => {
		const view = shallowMount(MailboxThread, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route: { name: 'mailbox', params: {}, query: {} },
					$router: { replace: vi.fn() },
				},
				stubs: {
					NcAppContent: { template: '<div><slot name="list" /></div>' },
				},
				directives: {
					infiniteScroll: {},
					shortkey: {},
				},
			},
			props: {
				account: { accountId: 1 },
				mailbox: { databaseId: 'priority', isPriorityInbox: true },
			},
		})

		const popovers = view.findAllComponents(NcPopover)

		expect(popovers.length).toBeGreaterThan(0)
		popovers.forEach((popover) => {
			expect(popover.props('triggers')).toEqual(['hover', 'focus'])
		})
	})
})
