/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import * as OutboxService from '../../../service/OutboxService.js'
import useOutboxStore from '../../../store/outboxStore.js'

vi.mock('../../../service/OutboxService.js')

describe('outboxStore enqueueMessage', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useOutboxStore()
		store.mainStore.convertComposerMessageToOutboxMutation = vi.fn()
	})

	it('stores the message on the server and keeps the returned copy', async () => {
		const data = { accountId: 1, subject: 'Unsubscribe' }
		OutboxService.enqueueMessage.mockResolvedValue({ ...data, id: 42 })

		const message = await store.enqueueMessage({ message: data })

		expect(OutboxService.enqueueMessage).toHaveBeenCalledWith(data)
		expect(message.id).toBe(42)
		expect(store.messages[42]).toEqual({ ...data, id: 42 })
		expect(store.messageList).toEqual([42])
	})

	it('does not add the message locally when the server rejects it', async () => {
		OutboxService.enqueueMessage.mockRejectedValue(new Error('Server error'))

		await expect(store.enqueueMessage({ message: { subject: 'Unsubscribe' } })).rejects.toThrow('Server error')

		expect(store.messageList).toEqual([])
	})
})
