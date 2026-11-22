/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import * as OutboxService from '../../../service/OutboxService.js'
import useOutboxStore from '../../../store/outboxStore.js'

vi.mock('../../../service/OutboxService.js')

let store

beforeEach(() => {
	setActivePinia(createPinia())
	store = useOutboxStore()
})

describe('outboxStore enqueueMessage', () => {
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

	it('leaves the message in the open composer alone', async () => {
		store.mainStore.startComposerSessionMutation({ type: 'imap', data: { id: 7, subject: 'Draft' } })
		OutboxService.enqueueMessage.mockResolvedValue({ id: 42, subject: 'Unsubscribe' })

		await store.enqueueMessage({ message: { subject: 'Unsubscribe' } })

		expect(store.mainStore.composerMessage.type).toBe('imap')
		expect(store.mainStore.composerMessage.data.id).toBe(7)
	})
})

describe('outboxStore enqueueFromDraft', () => {
	it('leaves the message in the open composer alone', async () => {
		store.mainStore.startComposerSessionMutation({ type: 'imap', data: { id: 7, subject: 'Draft' } })
		OutboxService.enqueueMessageFromDraft.mockResolvedValue({ id: 8, type: 0, subject: 'Other draft' })

		await store.enqueueFromDraft({ id: 8, draftMessage: { id: 8, subject: 'Other draft' } })

		expect(store.messages[8]).toEqual({ id: 8, type: 0, subject: 'Other draft' })
		expect(store.mainStore.composerMessage.type).toBe('imap')
		expect(store.mainStore.composerMessage.data.id).toBe(7)
	})
})
