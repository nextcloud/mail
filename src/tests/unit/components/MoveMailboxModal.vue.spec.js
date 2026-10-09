/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import { PiniaVuePlugin, setActivePinia } from 'pinia'
import MoveMailboxModal from '../../../components/MoveMailboxModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()
localVue.use(PiniaVuePlugin)
localVue.mixin(Nextcloud)

describe('MoveMailboxModal', () => {
	let store

	const mount = (mailbox) => shallowMount(MoveMailboxModal, {
		propsData: {
			account: { accountId: 1 },
			mailbox,
		},
		localVue,
	})

	beforeEach(() => {
		setActivePinia(createTestingPinia())
		store = useMainStore()
		store.renameMailbox = vi.fn().mockResolvedValue()
	})

	it('treats selecting the same mailbox as a no-op and closes', async () => {
		store.getMailbox = vi.fn().mockReturnValue({ name: 'INBOX' })
		const view = mount({ databaseId: 7, id: 'SU5CT1g=', displayName: 'INBOX', delimiter: '.' })
		view.vm.destMailboxId = 7

		await view.vm.onMove()

		expect(store.renameMailbox).not.toHaveBeenCalled()
		expect(view.vm.moving).toBe(false)
		expect(view.emitted().close).toBeTruthy()
	})

	it('moves the folder when the destination differs and resets state', async () => {
		store.getMailbox = vi.fn().mockReturnValue({ name: 'Archive' })
		const view = mount({ databaseId: 7, id: 'SU5CT1g=', displayName: 'INBOX', delimiter: '.' })
		view.vm.destMailboxId = 9

		await view.vm.onMove()

		expect(store.renameMailbox).toHaveBeenCalledWith({
			account: { accountId: 1 },
			mailbox: view.vm.mailbox,
			newName: 'Archive.INBOX',
		})
		expect(view.vm.moving).toBe(false)
		expect(view.emitted().close).toBeTruthy()
	})

	it('resets moving state after a failed move', async () => {
		store.getMailbox = vi.fn().mockReturnValue({ name: 'Archive' })
		store.renameMailbox = vi.fn().mockRejectedValue(new Error('IMAP error'))
		const view = mount({ databaseId: 7, id: 'SU5CT1g=', displayName: 'INBOX', delimiter: '.' })
		view.vm.destMailboxId = 9

		await view.vm.onMove()

		expect(view.vm.moving).toBe(false)
		expect(view.emitted().close).toBeTruthy()
	})
})
