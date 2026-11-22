/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showSuccess, showUndo } from '@nextcloud/dialogs'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { UNDO_DELAY } from '../../../store/constants.js'
import useOutboxStore from '../../../store/outboxStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
	showUndo: vi.fn(),
}))

describe('outboxStore', () => {
	let store

	beforeEach(() => {
		vi.useFakeTimers()
		setActivePinia(createPinia())
		store = useOutboxStore()
		store.messages[7] = { id: 7, sendAt: 1 }
		store.sendMessage = vi.fn().mockResolvedValue(true)
	})

	afterEach(() => {
		vi.useRealTimers()
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
})
