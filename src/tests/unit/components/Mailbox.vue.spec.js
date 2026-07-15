/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import Mailbox from '../../../components/Mailbox.vue'

describe('Mailbox handleShortcut', () => {
	const createContext = (envelopes, threadId) => ({
		envelopes,
		$route: { params: { mailboxId: 3, threadId: String(threadId) } },
		$router: { push: vi.fn() },
		hasDeleteAcl: vi.fn().mockReturnValue(true),
		hasSeenAcl: vi.fn().mockReturnValue(true),
		onDelete: vi.fn(),
		mainStore: {
			deleteThread: vi.fn().mockResolvedValue(),
			toggleEnvelopeSeen: vi.fn().mockResolvedValue(),
		},
	})

	const handleShortcut = (context, srcKey) => Mailbox.methods.handleShortcut.call(context, { srcKey })

	it('deletes the current thread on backspace', async () => {
		const env = { databaseId: 1, flags: {} }
		const context = createContext([env], 1)

		await handleShortcut(context, 'backspace')

		expect(context.onDelete).toHaveBeenCalledWith(1)
		expect(context.mainStore.deleteThread).toHaveBeenCalledWith({ envelope: env })
	})

	it('does not delete on backspace without the delete right', async () => {
		const context = createContext([{ databaseId: 1, flags: {} }], 1)
		context.hasDeleteAcl.mockReturnValue(false)

		await handleShortcut(context, 'backspace')

		expect(context.onDelete).not.toHaveBeenCalled()
		expect(context.mainStore.deleteThread).not.toHaveBeenCalled()
	})

	it('navigates to the next envelope', async () => {
		const context = createContext([{ databaseId: 1 }, { databaseId: 2 }], 1)

		await handleShortcut(context, 'next')

		expect(context.$router.push).toHaveBeenCalledWith(expect.objectContaining({
			params: expect.objectContaining({ threadId: 2 }),
		}))
	})

	it('does not navigate when the neighbour is the current envelope', async () => {
		const context = createContext([{ databaseId: 1 }, { databaseId: 1 }], 1)

		await handleShortcut(context, 'next')

		expect(context.$router.push).not.toHaveBeenCalled()
	})

	it('does not toggle seen for an envelope without flags', async () => {
		const context = createContext([{ databaseId: 1 }], 1)

		await handleShortcut(context, 'unseen')

		expect(context.hasSeenAcl).not.toHaveBeenCalled()
		expect(context.mainStore.toggleEnvelopeSeen).not.toHaveBeenCalled()
	})

	it('toggles seen for an envelope with flags', async () => {
		const env = { databaseId: 1, flags: { seen: false } }
		const context = createContext([env], 1)

		await handleShortcut(context, 'unseen')

		expect(context.mainStore.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: env })
	})
})
