/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import EnvelopeList from '../../../components/EnvelopeList.vue'
import NoTrashMailboxConfiguredError from '../../../errors/NoTrashMailboxConfiguredError.js'
import useMainStore from '../../../store/mainStore.js'

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
		const callsBeforeFirstResolve = deleteThread.mock.calls.length
		pending[0]()
		await flushPromises()
		pending[1]()
		await done

		expect(callsBeforeFirstResolve).toBe(1)
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

	it('tells the user when no trash folder is configured', async () => {
		const deleteThread = vi.fn().mockRejectedValue(new NoTrashMailboxConfiguredError())
		const context = createContext(deleteThread)

		await EnvelopeList.methods.deleteAllSelected.call(context)

		expect(deleteThread).toHaveBeenCalledTimes(1)
		expect(showError).toHaveBeenCalledTimes(1)
		expect(t).toHaveBeenCalledWith('mail', 'No trash folder configured')
	})
})

describe('EnvelopeList flag selection', () => {
	function createFlagContext(toggleEnvelopeSeen) {
		return {
			selectedEnvelopes: [{ databaseId: 1 }, { databaseId: 2 }],
			mainStore: { toggleEnvelopeSeen },
			updatingSelection: false,
			applyToSelection: EnvelopeList.methods.applyToSelection,
			unselectAll: vi.fn(),
		}
	}

	it('marks the selected messages as read one after another', async () => {
		const pending = []
		const toggleEnvelopeSeen = vi.fn(() => new Promise((resolve) => pending.push(resolve)))
		const context = createFlagContext(toggleEnvelopeSeen)

		const done = EnvelopeList.methods.markSelectedRead.call(context)
		await flushPromises()
		const callsBeforeFirstResolve = toggleEnvelopeSeen.mock.calls.length
		pending[0]()
		await flushPromises()
		pending[1]()
		await done

		expect(callsBeforeFirstResolve).toBe(1)
		expect(toggleEnvelopeSeen).toHaveBeenNthCalledWith(1, { envelope: context.selectedEnvelopes[0], seen: true })
		expect(toggleEnvelopeSeen).toHaveBeenNthCalledWith(2, { envelope: context.selectedEnvelopes[1], seen: true })
		expect(context.unselectAll).toHaveBeenCalled()
		expect(context.updatingSelection).toBe(false)
	})

	it('stops at the first message that cannot be marked', async () => {
		const toggleEnvelopeSeen = vi.fn().mockRejectedValue(new Error('denied'))
		const context = createFlagContext(toggleEnvelopeSeen)

		await EnvelopeList.methods.markSelectedUnread.call(context)

		expect(toggleEnvelopeSeen).toHaveBeenCalledTimes(1)
		expect(context.unselectAll).toHaveBeenCalled()
		expect(context.updatingSelection).toBe(false)
	})

	it('ignores a second run while one is in progress', async () => {
		const toggleEnvelopeSeen = vi.fn()
		const context = createFlagContext(toggleEnvelopeSeen)
		context.updatingSelection = true

		await EnvelopeList.methods.markSelectedRead.call(context)

		expect(toggleEnvelopeSeen).not.toHaveBeenCalled()
		expect(context.unselectAll).not.toHaveBeenCalled()
	})
})

describe('EnvelopeList junk selection', () => {
	let store
	let ctx

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useMainStore()
		store.accountsUnmapped[42] = { junkMailboxId: 10 }
		store.mailboxes[1] = { databaseId: 1, specialRole: 'inbox' }
		store.mailboxes[5] = { databaseId: 5, specialRole: 'junk' }
		store.toggleEnvelopeJunk = vi.fn()
		store.moveEnvelopeToJunk = vi.fn().mockResolvedValue(true)
		ctx = {
			mainStore: store,
			selectedEnvelopes: [],
			unselectAll: vi.fn(),
		}
	})

	it('offers not spam for an unflagged message in the junk mailbox', () => {
		const envelope = { accountId: 42, mailboxId: 5, flags: {} }
		ctx.selectedEnvelopes = [envelope]

		expect(EnvelopeList.computed.isAtLeastOneSelectedJunk.call(ctx)).toBe(true)
		expect(EnvelopeList.computed.isAtLeastOneSelectedNotJunk.call(ctx)).toBe(false)
	})

	it('marks unflagged messages in the junk mailbox as not junk', async () => {
		const envelope = { accountId: 42, mailboxId: 5, flags: {} }
		ctx.selectedEnvelopes = [envelope]

		await EnvelopeList.methods.markSelectionNotJunk.call(ctx)

		expect(store.toggleEnvelopeJunk).toHaveBeenCalledTimes(1)
		expect(store.toggleEnvelopeJunk).toHaveBeenCalledWith({ envelope, removeEnvelope: true })
	})

	it('does not mark an unflagged inbox message as not junk', async () => {
		const envelope = { accountId: 42, mailboxId: 1, flags: {} }
		ctx.selectedEnvelopes = [envelope]

		await EnvelopeList.methods.markSelectionNotJunk.call(ctx)

		expect(store.toggleEnvelopeJunk).not.toHaveBeenCalled()
	})
})
