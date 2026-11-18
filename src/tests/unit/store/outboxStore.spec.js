/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showSuccess, showUndo } from '@nextcloud/dialogs'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import * as OutboxService from '../../../service/OutboxService.js'
import { UNDO_DELAY } from '../../../store/constants.js'
import useOutboxStore from '../../../store/outboxStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
	showUndo: vi.fn(),
}))
vi.mock('../../../service/OutboxService.js')

describe('outboxStore', () => {
	let store

	beforeEach(() => {
		vi.useFakeTimers()
		setActivePinia(createPinia())
		store = useOutboxStore()
		store.messages[7] = { id: 7, sendAt: 1 }
		store.sendMessage = vi.fn().mockResolvedValue(true)
		OutboxService.updateMessage.mockImplementation(async (message) => ({ ...message }))
	})

	afterEach(() => {
		vi.useRealTimers()
		vi.resetAllMocks()
	})

	it('announces when the message will be sent and sends it after the undo delay', async () => {
		const sent = store.sendMessageWithUndo({ id: 7 })

		expect(showUndo).toHaveBeenCalledWith(
			'Message will be sent in 10 seconds',
			expect.any(Function),
			expect.objectContaining({ timeout: UNDO_DELAY }),
		)
		expect(store.sendMessage).not.toHaveBeenCalled()

		await vi.advanceTimersByTimeAsync(UNDO_DELAY)

		await expect(sent).resolves.toBe(true)
		expect(store.sendMessage).toHaveBeenCalledWith({ id: 7, force: false })
		expect(showSuccess).toHaveBeenCalledWith('Message sent')
	})

	it('stops a message by saving it without a send time', async () => {
		await store.stopMessage({ message: { id: 7, subject: 'Edited', sendAt: 1 } })

		expect(OutboxService.updateMessage).toHaveBeenCalledWith({ id: 7, subject: 'Edited', sendAt: null }, 7)
		expect(store.getMessage(7).sendAt).toBeNull()
	})

	describe('resumeMessage', () => {
		const sendAt = 1800000000

		it('schedules the saved state of a stopped message again', async () => {
			store.messages[7] = { id: 7, subject: 'Edited', sendAt: null }

			await store.resumeMessage({ id: 7, sendAt })

			expect(OutboxService.updateMessage).toHaveBeenCalledWith({ id: 7, subject: 'Edited', sendAt }, 7)
			expect(store.getMessage(7).sendAt).toBe(sendAt)
		})

		it('skips a message that is no longer in the outbox', async () => {
			await store.resumeMessage({ id: 8, sendAt })

			expect(OutboxService.updateMessage).not.toHaveBeenCalled()
		})

		it('waits for a pending update of the same message and resumes its result', async () => {
			let finishUpdate
			OutboxService.updateMessage.mockImplementationOnce(() => new Promise((resolve) => {
				finishUpdate = resolve
			}))

			const update = store.updateMessage({ id: 7, message: { id: 7, subject: 'Edit B', sendAt: null } })
			const resume = store.resumeMessage({ id: 7, sendAt })
			await vi.advanceTimersByTimeAsync(0)

			expect(OutboxService.updateMessage).toHaveBeenCalledTimes(1)

			finishUpdate({ id: 7, subject: 'Edit B', sendAt: null })
			await Promise.all([update, resume])

			expect(OutboxService.updateMessage).toHaveBeenLastCalledWith({ id: 7, subject: 'Edit B', sendAt }, 7)
			expect(store.getMessage(7).sendAt).toBe(sendAt)
		})

		it('resumes a message after a failed pending update', async () => {
			OutboxService.updateMessage.mockRejectedValueOnce(new Error('Database unavailable'))

			const update = store.updateMessage({ id: 7, message: { id: 7, subject: 'Edit B', sendAt: null } })
			const resume = store.resumeMessage({ id: 7, sendAt })

			await expect(update).rejects.toThrow('Database unavailable')
			await expect(resume).resolves.toEqual(expect.objectContaining({ sendAt }))
			expect(store.getMessage(7).sendAt).toBe(sendAt)
		})
	})
})
