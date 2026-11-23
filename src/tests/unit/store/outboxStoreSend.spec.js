/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import * as OutboxService from '../../../service/OutboxService.js'
import { STATUS_SMTP_ERROR, UNDO_DELAY } from '../../../store/constants.js'
import useOutboxStore from '../../../store/outboxStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
	showUndo: vi.fn(),
}))

vi.mock('../../../service/OutboxService.js', () => ({
	sendMessage: vi.fn(),
}))

vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal(),
	getLanguage: () => 'en',
}))

function rejectedResponse(data) {
	const error = new Error('Request failed with status code 500')
	error.response = { status: 500, data: { status: 'error', message: 'Could not send message', data } }
	return error
}

describe('outboxStore sending', () => {
	let store

	beforeEach(() => {
		vi.useFakeTimers()
		setActivePinia(createPinia())
		store = useOutboxStore()
		store.messages[7] = { id: 7, sendAt: 1, status: 0 }
	})

	afterEach(() => {
		vi.useRealTimers()
		vi.clearAllMocks()
	})

	it('stores the failed message reported by the server', async () => {
		const error = rejectedResponse([{ id: 7, status: STATUS_SMTP_ERROR, rejectedRecipients: ['a@example.test'] }])
		OutboxService.sendMessage.mockRejectedValue(error)

		await expect(store.sendMessage({ id: 7 })).rejects.toBe(error)

		expect(store.messages[7].status).toBe(STATUS_SMTP_ERROR)
		expect(store.messages[7].rejectedRecipients).toEqual(['a@example.test'])
	})

	it('rethrows the original error when the response has no message data', async () => {
		const error = rejectedResponse([])
		OutboxService.sendMessage.mockRejectedValue(error)

		await expect(store.sendMessage({ id: 7 })).rejects.toBe(error)

		expect(store.messages[7].status).toBe(0)
	})

	it('rethrows the original error when there is no response', async () => {
		const error = new Error('Network Error')
		OutboxService.sendMessage.mockRejectedValue(error)

		await expect(store.sendMessage({ id: 7 })).rejects.toBe(error)
	})

	it('shows the reason why the undo-delayed send failed', async () => {
		const error = rejectedResponse([{ id: 7, status: STATUS_SMTP_ERROR, rejectedRecipients: ['a@example.test'] }])
		OutboxService.sendMessage.mockRejectedValue(error)

		const sent = store.sendMessageWithUndo({ id: 7 }).catch((e) => e)
		await vi.advanceTimersByTimeAsync(UNDO_DELAY)

		expect(await sent).toBe(error)
		expect(showError).toHaveBeenCalledWith('The mail server rejected the recipient a@example.test. Check the address and try again.')
	})

	it('shows the generic error when the undo-delayed send has no message data', async () => {
		OutboxService.sendMessage.mockRejectedValue(rejectedResponse([]))

		const sent = store.sendMessageWithUndo({ id: 7 }).catch((e) => e)
		await vi.advanceTimersByTimeAsync(UNDO_DELAY)

		await sent
		expect(showError).toHaveBeenCalledWith('Could not send message')
	})
})
