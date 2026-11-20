/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { flushPromises } from '@vue/test-utils'
import EnvelopeList from '../../../components/EnvelopeList.vue'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
}))

function createContext(deleteThread) {
	const envelopes = [
		{ databaseId: 1, threadRootId: 'a' },
		{ databaseId: 2, threadRootId: 'b' },
		{ databaseId: 3, threadRootId: 'c' },
	]
	return {
		selectedEnvelopes: envelopes.slice(0, 2),
		sortedEnvelops: envelopes,
		$route: { params: { mailboxId: 10, threadId: 99 } },
		$router: { push: vi.fn() },
		mainStore: { deleteThread },
		unselectAll: vi.fn(),
	}
}

describe('EnvelopeList', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('deletes the selected threads one after another', async () => {
		const pending = []
		const deleteThread = vi.fn(() => new Promise((resolve) => pending.push(resolve)))
		const context = createContext(deleteThread)

		const done = EnvelopeList.methods.deleteAllSelected.call(context)
		await flushPromises()
		const callsBeforeFirstFinished = deleteThread.mock.calls.length
		pending[0]()
		await flushPromises()
		pending[1]()
		await done

		expect(callsBeforeFirstFinished).toBe(1)
		expect(deleteThread).toHaveBeenCalledTimes(2)
		expect(deleteThread).toHaveBeenNthCalledWith(2, { envelope: context.selectedEnvelopes[1] })
		expect(context.unselectAll).toHaveBeenCalled()
	})

	it('stops at the first failed thread', async () => {
		const deleteThread = vi.fn().mockRejectedValue(new Error('denied'))
		const context = createContext(deleteThread)

		await EnvelopeList.methods.deleteAllSelected.call(context)

		expect(deleteThread).toHaveBeenCalledTimes(1)
		expect(showError).toHaveBeenCalledTimes(1)
		expect(context.unselectAll).toHaveBeenCalled()
	})
})
