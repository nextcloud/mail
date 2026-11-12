/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { setActivePinia } from 'pinia'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NewMessageButtonHeader from '../../../components/NewMessageButtonHeader.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('NewMessageButtonHeader', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()

		store.getMailbox = vi.fn().mockReturnValue({ databaseId: 5, accountId: 1 })
		store.getAccount = vi.fn().mockReturnValue({ id: 1 })
	})

	const mountHeader = () => mount(NewMessageButtonHeader, {
		global: {
			mixins: [Nextcloud],
			directives: { shortkey: {} },
			mocks: {
				$route: { name: 'mailbox', params: { mailboxId: 5 } },
			},
		},
	})

	it('shows the loading icon while the R shortcut refreshes', async () => {
		store.syncEnvelopes = vi.fn().mockReturnValue(new Promise(() => {}))
		const view = mountHeader()

		await view.find('.refresh__button').trigger('shortkey')

		expect(store.syncEnvelopes).toHaveBeenCalledWith({ mailboxId: 5 })
		expect(view.findComponent(NcLoadingIcon).exists()).toBe(true)
		expect(view.find('.refresh__button').attributes('disabled')).toBeDefined()
	})

	it('starts a blank message on the C shortcut', async () => {
		const view = mountHeader()

		await view.find('button').trigger('shortkey')

		expect(store.startComposerSession).toHaveBeenCalledWith({ isBlankMessage: true })
	})
})
