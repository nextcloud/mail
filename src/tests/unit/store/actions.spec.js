/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { curry, range, reverse } from 'ramda'
import * as AccountService from '../../../service/AccountService.js'
import * as InternalAddressService from '../../../service/InternalAddressService.js'
import * as MailboxService from '../../../service/MailboxService.js'
import * as MessageService from '../../../service/MessageService.js'
import * as NotificationService from '../../../service/NotificationService.js'
import * as OutboxService from '../../../service/OutboxService.js'
import * as PreferenceService from '../../../service/PreferenceService.js'
import { PAGE_SIZE, UNIFIED_INBOX_ID } from '../../../store/constants.js'
import useMainStore from '../../../store/mainStore.js'
import useOutboxStore from '../../../store/outboxStore.js'
import { normalizedEnvelopeListId } from '../../../util/normalization.js'

vi.mock('../../../service/AccountService.js')
vi.mock('../../../service/InternalAddressService.js')
vi.mock('../../../service/MailboxService.js')
vi.mock('../../../service/MessageService.js')
vi.mock('../../../service/NotificationService.js')
vi.mock('../../../service/OutboxService.js')
vi.mock('../../../service/PreferenceService.js')
vi.mock('../../../util/normalization.js', () => ({
	__esModule: true,
	// Supply a default list id ('') to prevent annoying errors
	normalizedEnvelopeListId: vi.fn(() => ''),
}))

const mockEnvelope = curry((mailboxId, uid) => ({
	databaseId: mailboxId * 1000 + uid,
	mailboxId,
	uid,
	dateInt: uid * 10000,
	threadRootId: Math.random().toString(),
}))

describe('Vuex store actions', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('creates a mailbox', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
			mailboxes: [],
		}
		const name = 'Important'
		const mailbox = {
			name: 'Important',
		}

		store.addAccountMutation(account)

		MailboxService.create.mockResolvedValue(mailbox)

		const result = await store.createMailbox({ account, name })

		expect(result).toEqual(mailbox)
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'Important')
	})

	it('unfolds a folded account when a mailbox is created in it', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
			mailboxes: [],
			folded: true,
		}
		MailboxService.create.mockResolvedValue({ name: 'Important' })
		PreferenceService.savePreference.mockResolvedValue(undefined)
		store.addAccountMutation(account)

		await store.createMailbox({ account: store.accountsUnmapped[13], name: 'Important' })

		expect(store.accountsUnmapped[13].folded).toBe(false)
		expect(PreferenceService.savePreference).toHaveBeenCalledWith(
			'account-settings',
			JSON.stringify([{ accountId: 13, collapsed: false, folded: false }]),
		)
	})

	it('still creates the mailbox when saving the unfolded state fails', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
			mailboxes: [],
			folded: true,
		}
		const mailbox = { name: 'Important' }
		MailboxService.create.mockResolvedValue(mailbox)
		PreferenceService.savePreference.mockRejectedValue(new Error('network down'))
		store.addAccountMutation(account)

		const result = await store.createMailbox({ account: store.accountsUnmapped[13], name: 'Important' })

		expect(result).toEqual(mailbox)
		expect(store.accountsUnmapped[13].folded).toBe(false)
	})

	it('does not save the folded state when a mailbox is created in an unfolded account', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
			mailboxes: [],
		}
		MailboxService.create.mockResolvedValue({ name: 'Important' })
		store.addAccountMutation(account)

		await store.createMailbox({ account: store.accountsUnmapped[13], name: 'Important' })

		expect(PreferenceService.savePreference).not.toHaveBeenCalled()
	})

	it('saves account settings in order so an older save cannot overwrite a newer one', async () => {
		store.addAccountMutation({ id: 13, personalNamespace: '', mailboxes: [] })
		store.addAccountMutation({ id: 14, personalNamespace: '', mailboxes: [] })
		let finishFirstSave
		PreferenceService.savePreference
			.mockImplementationOnce(() => new Promise((resolve) => {
				finishFirstSave = resolve
			}))
			.mockResolvedValue(undefined)

		const first = store.toggleAccountFolded(13)
		const second = store.toggleAccountFolded(14)
		await vi.waitFor(() => expect(finishFirstSave).toBeDefined())

		expect(PreferenceService.savePreference).toHaveBeenCalledTimes(1)

		finishFirstSave()
		await Promise.all([first, second])

		expect(PreferenceService.savePreference).toHaveBeenCalledTimes(2)
		expect(PreferenceService.savePreference).toHaveBeenLastCalledWith(
			'account-settings',
			JSON.stringify([{ accountId: 13, folded: true }, { accountId: 14, folded: true }]),
		)
	})

	it('keeps saving account settings after a failed save', async () => {
		store.addAccountMutation({ id: 13, personalNamespace: '', mailboxes: [] })
		store.addAccountMutation({ id: 14, personalNamespace: '', mailboxes: [] })
		PreferenceService.savePreference
			.mockRejectedValueOnce(new Error('network down'))
			.mockResolvedValue(undefined)

		await expect(store.toggleAccountFolded(13)).rejects.toThrow('network down')
		await store.toggleAccountFolded(14)

		expect(store.accountsUnmapped[14].folded).toBe(true)
		expect(PreferenceService.savePreference).toHaveBeenLastCalledWith(
			'account-settings',
			JSON.stringify([{ accountId: 13, folded: false }, { accountId: 14, folded: true }]),
		)
	})

	it('does not undo a newer change when an older folded save fails', async () => {
		store.addAccountMutation({ id: 13, personalNamespace: '', mailboxes: [] })
		MailboxService.create.mockResolvedValue({ name: 'Important' })
		let failSave
		PreferenceService.savePreference
			.mockImplementationOnce(() => new Promise((resolve, reject) => {
				failSave = reject
			}))
			.mockResolvedValue(undefined)

		const toggle = store.toggleAccountFolded(13)
		await vi.waitFor(() => expect(failSave).toBeDefined())
		const unfold = store.createMailbox({ account: store.accountsUnmapped[13], name: 'Important' })
		await vi.waitFor(() => expect(store.accountsUnmapped[13].folded).toBe(false))
		failSave(new Error('network down'))

		await expect(toggle).rejects.toThrow('network down')
		await unfold
		expect(store.accountsUnmapped[13].folded).toBe(false)
	})

	it('does not undo a newer change that ends in the same folded state when an older save fails', async () => {
		store.addAccountMutation({ id: 13, personalNamespace: '', mailboxes: [] })
		MailboxService.create.mockResolvedValue({ name: 'Important' })
		let failSave
		PreferenceService.savePreference
			.mockImplementationOnce(() => new Promise((resolve, reject) => {
				failSave = reject
			}))
			.mockResolvedValue(undefined)

		const firstToggle = store.toggleAccountFolded(13)
		await vi.waitFor(() => expect(failSave).toBeDefined())
		const unfold = store.createMailbox({ account: store.accountsUnmapped[13], name: 'Important' })
		await vi.waitFor(() => expect(store.accountsUnmapped[13].folded).toBe(false))
		const secondToggle = store.toggleAccountFolded(13)
		failSave(new Error('network down'))

		await expect(firstToggle).rejects.toThrow('network down')
		await Promise.all([unfold, secondToggle])
		expect(store.accountsUnmapped[13].folded).toBe(true)
	})

	it('restores the folded state when saving it fails', async () => {
		store.addAccountMutation({
			id: 13,
			personalNamespace: '',
			mailboxes: [],
		})
		const error = new Error('network down')
		PreferenceService.savePreference.mockRejectedValue(error)

		await expect(store.toggleAccountFolded(13)).rejects.toThrow(error)

		expect(store.accountsUnmapped[13].folded).toBe(false)
		expect(store.allAccountSettings).toContainEqual({ accountId: 13, folded: false })
	})

	it('toggles and persists the folded state of an account', async () => {
		store.addAccountMutation({
			id: 13,
			personalNamespace: '',
			mailboxes: [],
		})
		PreferenceService.savePreference.mockResolvedValue(undefined)

		await store.toggleAccountFolded(13)

		expect(store.accountsUnmapped[13].folded).toBe(true)
		expect(PreferenceService.savePreference).toHaveBeenCalledWith(
			'account-settings',
			JSON.stringify([{ accountId: 13, folded: true }]),
		)

		await store.toggleAccountFolded(13)

		expect(store.accountsUnmapped[13].folded).toBe(false)
		expect(PreferenceService.savePreference).toHaveBeenLastCalledWith(
			'account-settings',
			JSON.stringify([{ accountId: 13, folded: false }]),
		)
	})

	it('creates a sub-mailbox', async () => {
		const account = {
			id: 13,
			personalNamespace: '',
			mailboxes: [],
		}
		const name = 'Archive.2020'
		const mailbox = {
			name: 'Archive.2020',
		}

		store.addAccountMutation(account)

		MailboxService.create.mockResolvedValue(mailbox)

		const result = await store.createMailbox({ account, name })

		expect(result).toEqual(mailbox)
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'Archive.2020')
	})

	it('adds a prefix to new mailboxes if the account has a personal namespace', async () => {
		const account = {
			id: 13,
			personalNamespace: 'INBOX.',
			mailboxes: [],
		}
		const name = 'Important'
		const mailbox = {
			name: 'INBOX.Important',
		}

		store.addAccountMutation(account)

		MailboxService.create.mockResolvedValue(mailbox)

		const result = await store.createMailbox({ account, name })

		expect(result).toEqual(mailbox)
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'INBOX.Important')
	})

	it('adds no prefix to new sub-mailboxes if the account has a personal namespace', async () => {
		const account = {
			id: 13,
			personalNamespace: 'INBOX.',
			mailboxes: [],
		}
		const name = 'INBOX.Archive.2020'
		const mailbox = {
			name: 'INBOX.Archive.2020',
		}

		store.addAccountMutation(account)

		MailboxService.create.mockResolvedValue(mailbox)

		const result = await store.createMailbox({ account, name })

		expect(result).toEqual(mailbox)
		expect(MailboxService.create).toHaveBeenCalledWith(13, 'INBOX.Archive.2020')
	})

	it('combines unified inbox even if no inboxes are present', async () => {
		const envelopes = await store.fetchEnvelopes({
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).toEqual([])
	})

	it('creates a unified page from one mailbox', async () => {
		const account = {
			id: 13,
			personalNamespace: 'INBOX.',
			mailboxes: [],
		}

		store.addAccountMutation(account)
		store.addMailboxMutation({
			account,
			mailbox: {
				id: 'INBOX',
				name: 'INBOX',
				databaseId: 21,
				accountId: 13,
				specialRole: 'inbox',
			},
		})
		store.addMailboxMutation({
			account,
			mailbox: {
				id: 'Drafts',
				name: 'Drafts',
				databaseId: 22,
				accountId: 13,
				specialRole: 'draft',
			},
		})

		store.addEnvelopesMutation = vi.fn()

		MessageService.fetchEnvelopes.mockReturnValueOnce(Promise.resolve([{
			databaseId: 123,
			mailboxId: 21,
			uid: 321,
			subject: 'msg1',
		}]))

		const envelopes = await store.fetchEnvelopes({
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).toEqual([
			{
				databaseId: 123,
				mailboxId: 21,
				uid: 321,
				subject: 'msg1',
			},
		])
		expect(store.addEnvelopesMutation).toBeCalledWith({
			envelopes: [{
				databaseId: 123,
				mailboxId: 21,
				uid: 321,
				subject: 'msg1',
			}],
			query: undefined,
		})
	})

	it('fetches the next individual page', async () => {
		const msgs1 = reverse(range(30, 40))
		const page1 = reverse(range(10, 30))

		const account13 = {
			id: 13,
		}

		store.preferences['sort-order'] = 'newest'
		store.preferences['layout-message-view'] = 'threaded'

		store.addAccountMutation(account13)
		store.addMailboxMutation({
			account: account13,
			mailbox: {
				name: 'INBOX',
				databaseId: 11,
				specialRole: 'inbox',
			},
		})
		store.addMailboxMutation({
			account: account13,
			mailbox: {
				name: 'Drafts',
				databaseId: 12,
				specialRole: 'draft',
			},
		})

		// Add initial envelopes
		store.addEnvelopesMutation({
			envelopes: msgs1.map(mockEnvelope(11)),
			addToUnifiedMailboxes: true,
		})

		// Mock fetching next pages
		MessageService.fetchEnvelopes.mockImplementation(async (accountId, mailboxId) => {
			if (accountId !== 13 || mailboxId !== 11) {
				return []
			}

			return page1.map(mockEnvelope(11))
		})

		await store.fetchNextEnvelopePage({
			mailboxId: UNIFIED_INBOX_ID,
			quantity: PAGE_SIZE,
		})

		expect(MessageService.fetchEnvelopes).toHaveBeenCalledTimes(1)
		expect(MessageService.fetchEnvelopes)
			.toHaveBeenNthCalledWith(1, 13, 11, undefined, 300000, PAGE_SIZE, 'newest', 'threaded')
		expect(store.mailboxes[UNIFIED_INBOX_ID].envelopeLists[''].toSorted()).toEqual([
			// Initial envelopes
			...msgs1.map(mockEnvelope(11)),

			// Fetched page for mailbox 11
			...page1.map(mockEnvelope(11)),
		].map((e) => e.databaseId).sort())
		expect(store.mailboxes[11].envelopeLists[''].toSorted()).toEqual([
			// Initial envelopes
			...msgs1.map(mockEnvelope(11)),

			// Fetched page for mailbox 11
			...page1.map(mockEnvelope(11)),
		].map((e) => e.databaseId).sort())
	})

	it('builds the next unified page with local data', async () => {
		const msgs1 = reverse(range(20, 70))
		const page1 = reverse(range(50, 60))

		const account13 = {
			id: 13,
		}

		store.preferences['sort-order'] = 'newest'

		store.addAccountMutation(account13)
		store.addMailboxMutation({
			account: account13,
			mailbox: {
				name: 'INBOX',
				databaseId: 11,
				specialRole: 'inbox',
			},
		})
		store.addMailboxMutation({
			account: account13,
			mailbox: {
				name: 'Drafts',
				databaseId: 12,
				specialRole: 'draft',
			},
		})

		// Add initial envelopes
		store.addEnvelopesMutation({
			envelopes: msgs1.map(mockEnvelope(11)),
			addToUnifiedMailboxes: false,
		})

		// Also add some envelopes to the unified mailbox
		store.addEnvelopesMutation({
			envelopes: page1.map(mockEnvelope(11)),
			addToUnifiedMailboxes: true,
		})

		// Mock fetching next pages (not called but makes failures easier to understand)
		MessageService.fetchEnvelopes.mockImplementation(async () => {
			throw new Error('Tried to fetch messages')
		})

		await store.fetchNextEnvelopePage({
			mailboxId: UNIFIED_INBOX_ID,
			quantity: PAGE_SIZE,
		})

		expect(MessageService.fetchEnvelopes).not.toHaveBeenCalled()
		expect(store.mailboxes[UNIFIED_INBOX_ID].envelopeLists[''].toSorted()).toEqual([
			// Initial envelopes
			...page1.map(mockEnvelope(11)),

			// Envelopes loaded from local state
			...range(30, 50).map(mockEnvelope(11)),
		].map((e) => e.databaseId).sort())
	})

	it('builds the next unified page with partial fetch', async () => {
		const page1 = reverse(range(30, 35))
		const page2 = reverse(range(25, 30))
		const msgs2 = reverse(range(60, 70))

		const account13 = {
			id: 13,
		}
		const account26 = {
			id: 26,
		}

		store.preferences['sort-order'] = 'newest'
		store.preferences['layout-message-view'] = 'threaded'

		store.addAccountMutation(account13)
		store.addAccountMutation(account26)
		store.addMailboxMutation({
			account: account13,
			mailbox: {
				name: 'INBOX',
				databaseId: 11,
				specialRole: 'inbox',
			},
		})
		store.addMailboxMutation({
			account: account13,
			mailbox: {
				name: 'Drafts',
				databaseId: 12,
				specialRole: 'draft',
			},
		})
		store.addMailboxMutation({
			account: account26,
			mailbox: {
				name: 'INBOX',
				databaseId: 21,
				specialRole: 'inbox',
			},
		})
		store.addMailboxMutation({
			account: account26,
			mailbox: {
				name: 'Drafts',
				databaseId: 22,
				specialRole: 'draft',
			},
		})

		// Add initial pages
		store.addEnvelopesMutation({
			envelopes: page1.map(mockEnvelope(11)),
		})
		store.addEnvelopesMutation({
			envelopes: msgs2.map(mockEnvelope(21)),
		})

		// Mock fetching next pages
		MessageService.fetchEnvelopes.mockImplementation(async (
			accountId,
			mailboxId,
			query,
			cursor,
			limit,
			sortOrder,
		) => {
			if (accountId !== 13 || mailboxId !== 11) {
				return []
			}

			expect(sortOrder).toBe('newest')
			return page2.map(mockEnvelope(11)).filter((e) => e.dateInt < cursor).slice(0, limit)
		})

		await store.fetchNextEnvelopePage({
			mailboxId: UNIFIED_INBOX_ID,
			quantity: PAGE_SIZE,
		})

		expect(MessageService.fetchEnvelopes).toHaveBeenCalledTimes(2)
		expect(MessageService.fetchEnvelopes)
			.toHaveBeenNthCalledWith(1, 13, 11, undefined, 300000, PAGE_SIZE, 'newest', 'threaded')
		expect(MessageService.fetchEnvelopes)
			.toHaveBeenNthCalledWith(2, 26, 21, undefined, 600000, PAGE_SIZE, 'newest', 'threaded')
		expect(store.mailboxes[UNIFIED_INBOX_ID].envelopeLists[''].toSorted()).toEqual([
			// Initial envelopes
			...page1.map(mockEnvelope(11)),
			...msgs2.map(mockEnvelope(21)),

			// Fetched page for mailbox 11
			...page2.map(mockEnvelope(11)),
		].map((e) => e.databaseId).sort())
		expect(store.mailboxes[11].envelopeLists[''].toSorted()).toEqual([
			// Initial envelopes
			...page1.map(mockEnvelope(11)),

			// Fetched page for mailbox 11
			...page2.map(mockEnvelope(11)),
		].map((e) => e.databaseId).sort())
		expect(store.mailboxes[21].envelopeLists[''].toSorted())
			.toEqual(msgs2.map(mockEnvelope(21)).map((e) => e.databaseId).toSorted())
	})

	describe('thread unread state', () => {
		const envelope = (databaseId, seen, extra = {}) => ({
			databaseId,
			accountId: 13,
			mailboxId: 27,
			threadRootId: 'root',
			flags: { seen },
			...extra,
		})
		const controlledWrites = () => {
			const writes = {}
			MessageService.setEnvelopeFlags.mockImplementation((id) => new Promise((resolve, reject) => {
				writes[id] = { resolve, reject }
			}))
			return writes
		}

		beforeEach(() => {
			store.mailboxes[27] = { databaseId: 27, accountId: 13, unread: 0 }
		})

		it('only restores the seen flag when a write fails', async () => {
			const row = envelope(3, true, { threadHasUnread: false })
			store.envelopes = { 3: row }
			MessageService.setEnvelopeFlags.mockRejectedValue(new Error('offline'))

			await expect(store.toggleEnvelopeSeen({ envelope: row, seen: false })).rejects.toThrow('offline')

			expect(row.flags.seen).toBe(true)
			expect(row.threadHasUnread).toBe(false)
			expect(store.getThreadHasUnread(row)).toBe(false)
		})

		it('uses the server value for a thread that is not loaded', () => {
			expect(store.getThreadHasUnread(envelope(3, true, { threadHasUnread: true }))).toBe(true)
			expect(store.getThreadHasUnread(envelope(3, true, { threadHasUnread: false }))).toBe(false)
			expect(store.getThreadHasUnread(envelope(3, true))).toBe(false)
		})

		it('considers known unread messages of a thread that is not loaded', async () => {
			const older = envelope(1, true)
			const row = envelope(3, true, { threadHasUnread: false })
			store.envelopes = { 1: older, 3: row }
			const writes = controlledWrites()

			const pendingOlder = store.toggleEnvelopeSeen({ envelope: older, seen: false })
			writes[1].resolve({})
			await pendingOlder
			const pendingRow = store.toggleEnvelopeSeen({ envelope: row, seen: false })
			writes[3].reject(new Error('offline'))
			await expect(pendingRow).rejects.toThrow('offline')

			expect(row.flags.seen).toBe(true)
			expect(store.getThreadHasUnread(row)).toBe(true)
		})

		it('keeps the server value for a thread that is not loaded when the known messages are read', () => {
			const row = envelope(3, true, { threadHasUnread: true })
			store.envelopes = { 1: envelope(1, true), 3: row }

			expect(store.getThreadHasUnread(row)).toBe(true)
		})

		it('derives the state from the messages of a loaded thread', () => {
			const row = envelope(3, true, { threadHasUnread: true, thread: [1, 3] })
			store.envelopes = { 1: envelope(1, true), 3: row }

			expect(store.getThreadHasUnread(row)).toBe(false)

			store.envelopes[1].flags.seen = false

			expect(store.getThreadHasUnread(row)).toBe(true)
		})

		it('ignores deleted messages and other mailboxes', () => {
			const row = envelope(3, true, { threadHasUnread: true, thread: [1, 2, 3] })
			store.envelopes = {
				1: envelope(1, false, { flags: { seen: false, deleted: true } }),
				2: envelope(2, false, { mailboxId: 28 }),
				3: row,
			}

			expect(store.getThreadHasUnread(row)).toBe(false)
		})

		it('stays conservative when a known message is missing from the loaded thread', () => {
			const read = envelope(3, true, { threadHasUnread: true, thread: [3] })
			store.envelopes = { 3: read, 5: envelope(5, true) }

			expect(store.getThreadHasUnread(read)).toBe(true)

			read.threadHasUnread = false
			store.envelopes[5].flags.seen = false

			expect(store.getThreadHasUnread(read)).toBe(true)
		})

		it('keeps the thread unread when another message became unread while a write was pending', async () => {
			const a = envelope(1, true, { thread: [1, 3] })
			const b = envelope(3, true, { thread: [1, 3] })
			store.envelopes = { 1: a, 3: b }
			const writes = controlledWrites()

			const pendingA = store.toggleEnvelopeSeen({ envelope: a, seen: false })
			const pendingB = store.toggleEnvelopeSeen({ envelope: b, seen: false })
			writes[3].resolve({})
			await pendingB
			writes[1].reject(new Error('offline'))
			await expect(pendingA).rejects.toThrow('offline')

			expect(store.getThreadHasUnread(b)).toBe(true)
		})

		it.each([
			['A then B', [1, 3]],
			['B then A', [3, 1]],
		])('clears the thread unread state when two pending writes fail (%s)', async (name, order) => {
			const a = envelope(1, true, { thread: [1, 3] })
			const b = envelope(3, true, { thread: [1, 3] })
			store.envelopes = { 1: a, 3: b }
			const writes = controlledWrites()

			const pending = {
				1: store.toggleEnvelopeSeen({ envelope: a, seen: false }).catch(() => {}),
				3: store.toggleEnvelopeSeen({ envelope: b, seen: false }).catch(() => {}),
			}
			for (const id of order) {
				writes[id].reject(new Error('offline'))
				await pending[id]
			}

			expect(store.getThreadHasUnread(b)).toBe(false)
		})

		it('keeps a successful read when a later write on the thread fails', async () => {
			const a = envelope(1, false, { threadHasUnread: true, thread: [1, 3] })
			const b = envelope(3, true, { threadHasUnread: true, thread: [1, 3] })
			store.envelopes = { 1: a, 3: b }
			const writes = controlledWrites()

			const pendingB = store.toggleEnvelopeSeen({ envelope: b, seen: false })
			const pendingA = store.toggleEnvelopeSeen({ envelope: a, seen: true })
			writes[1].resolve({})
			await pendingA
			writes[3].reject(new Error('offline'))
			await expect(pendingB).rejects.toThrow('offline')

			expect(store.getThreadHasUnread(b)).toBe(false)
		})
	})

	describe('inbox sync', () => {
		it('fetches the inbox first', async () => {
			const account13 = {
				id: 13,
			}
			const account26 = {
				id: 26,
			}

			store.addAccountMutation(account13)
			store.addAccountMutation(account26)
			store.addMailboxMutation({
				account: account13,
				mailbox: {
					name: 'INBOX',
					databaseId: 11,
					specialRole: 'inbox',
				},
			})
			store.addMailboxMutation({
				account: account13,
				mailbox: {
					name: 'Drafts',
					databaseId: 12,
					specialRole: 'draft',
				},
			})
			store.addMailboxMutation({
				account: account26,
				mailbox: {
					name: 'INBOX',
					databaseId: 21,
					specialRole: 'inbox',
				},
			})
			store.addMailboxMutation({
				account: account26,
				mailbox: {
					name: 'Drafts',
					databaseId: 22,
					specialRole: 'draft',
				},
			})

			store.fetchEnvelopes = vi.fn(async () => {})
			store.syncEnvelopes = vi.fn(async () => {})

			await store.syncInboxes()

			expect(store.fetchEnvelopes).toHaveBeenCalledTimes(2)
			expect(store.fetchEnvelopes).toHaveBeenNthCalledWith(1, {
				mailboxId: 11,
			})
			expect(store.fetchEnvelopes).toHaveBeenNthCalledWith(2, {
				mailboxId: 21,
			})

			expect(store.syncEnvelopes).toHaveBeenCalledTimes(2)
			expect(store.syncEnvelopes).toHaveBeenNthCalledWith(1, {
				mailboxId: 11,
			})
			expect(store.syncEnvelopes).toHaveBeenNthCalledWith(2, {
				mailboxId: 21,
			})

			// We can't detect new messages here
			expect(NotificationService.showNewMessagesNotification).not.toHaveBeenCalled()
		})

		it('syncs each individual mailbox', async () => {
			const account13 = {
				id: 13,
			}
			const account26 = {
				id: 26,
			}

			store.addAccountMutation(account13)
			store.addAccountMutation(account26)
			store.addMailboxMutation({
				account: account13,
				mailbox: {
					name: 'INBOX',
					databaseId: 11,
					specialRole: 'inbox',
				},
			})
			store.addMailboxMutation({
				account: account13,
				mailbox: {
					name: 'Drafts',
					databaseId: 12,
					specialRole: 'draft',
				},
			})
			store.addMailboxMutation({
				account: account26,
				mailbox: {
					name: 'INBOX',
					databaseId: 21,
					specialRole: 'inbox',
				},
			})
			store.addMailboxMutation({
				account: account26,
				mailbox: {
					name: 'Drafts',
					databaseId: 22,
					specialRole: 'draft',
				},
			})

			// Mock a pseudo envelope list for each mailbox to simulate existing mailboxes with
			// envelopes (and make sure that store.fetchEnvelopes() is not called).
			const envelopeListId = Symbol()
			normalizedEnvelopeListId.mockReturnValue(envelopeListId)
			for (const mailbox of Object.values(store.mailboxes)) {
				mailbox.envelopeLists[envelopeListId] = {}
			}

			store.fetchEnvelopes = vi.fn(async () => {})
			store.syncEnvelopes = vi.fn(async () => [{ id: 123 }, { id: 321 }])

			await store.syncInboxes()

			expect(store.fetchEnvelopes).not.toHaveBeenCalled()
			expect(store.syncEnvelopes).toHaveBeenCalledTimes(4)
			expect(store.syncEnvelopes).toHaveBeenNthCalledWith(1, {
				mailboxId: 11,
			})
			expect(store.syncEnvelopes).toHaveBeenNthCalledWith(2, {
				mailboxId: 21,
			})
			expect(store.syncEnvelopes).toHaveBeenNthCalledWith(3, {
				mailboxId: UNIFIED_INBOX_ID,
				query: 'is:pi-important',
			})
			expect(store.syncEnvelopes).toHaveBeenNthCalledWith(4, {
				mailboxId: UNIFIED_INBOX_ID,
				query: 'is:pi-other',
			})
			// Here we expect notifications
			expect(NotificationService.showNewMessagesNotification).toHaveBeenCalled()
		})
	})

	it('should move message to junk, no mailbox configured', async () => {
		store.addAccountMutation({
			id: 42,
			junkMailboxId: null,
		})

		const removeEnvelope = await store.moveEnvelopeToJunk({
			accountId: 42,
			flags: {
				$junk: true,
			},
			mailboxId: 1,
		})

		expect(removeEnvelope).toBeFalsy()
	})

	it('should move message to inbox', async () => {
		const account = {
			id: 42,
			junkMailboxId: 10,
		}

		store.addAccountMutation(account)
		store.addMailboxMutation({
			account,
			mailbox: {
				databaseId: 1,
				specialRole: 'inbox',
				name: 'INBOX',
			},
		})

		const removeEnvelope = await store.moveEnvelopeToJunk({
			accountId: 42,
			flags: {
				$junk: true,
			},
			mailboxId: 10,
		})

		expect(removeEnvelope).toBeTruthy()
	})

	it('should move message to inbox, inbox not found', async () => {
		store.addAccountMutation({
			id: 42,
			junkMailboxId: 10,
		})

		const removeEnvelope = await store.moveEnvelopeToJunk({
			accountId: 42,
			flags: {
				$junk: true,
			},
			mailboxId: 10,
		})

		expect(removeEnvelope).toBeFalsy()
	})

	it('includes a cache buster if requested', async () => {
		const account = {
			id: 13,
			personalNamespace: 'INBOX.',
			mailboxes: [],
		}

		store.addAccountMutation(account)
		store.addMailboxMutation({
			account,
			mailbox: {
				id: 'INBOX',
				name: 'INBOX',
				databaseId: 21,
				accountId: 13,
				specialRole: 'inbox',
				cacheBuster: 'abcdef123',
			},
		})

		store.addEnvelopesMutation = vi.fn()

		MessageService.fetchEnvelopes.mockResolvedValueOnce([])

		await store.fetchEnvelopes({
			mailboxId: 21,
			includeCacheBuster: true,
		})

		expect(MessageService.fetchEnvelopes).toHaveBeenCalledWith(
			13, // account id
			21, // mailbox id
			undefined, // query
			undefined, // cursor
			20, // limit (PAGE_SIZE)
			undefined, // sort ordre
			undefined, // layout
			'abcdef123', // cache buster
		)
	})

	describe('prepareAttachments', () => {
		const original = {
			mailboxId: 1,
			attachments: [{ id: 'att1', messageId: 42, fileName: 'doc.pdf' }],
			inlineAttachments: [{ id: 'img1', messageId: 42, cid: 'abc@x', fileName: 'logo.png' }],
		}

		it('reply includes only inline attachments', () => {
			const result = store.prepareAttachments(original)

			expect(result).toHaveLength(1)
			expect(result[0]).toMatchObject({
				id: 'img1',
				type: 'message-attachment-inline',
				mailboxId: 1,
				uid: 42,
			})
		})

		it('forward includes regular and inline attachments', () => {
			const result = store.prepareAttachments(original, true)

			expect(result).toHaveLength(2)
			expect(result[0]).toMatchObject({
				id: 'att1',
				type: 'message-attachment',
				mailboxId: 1,
				uid: 42,
			})
			expect(result[1]).toMatchObject({
				id: 'img1',
				type: 'message-attachment-inline',
				mailboxId: 1,
				uid: 42,
			})
		})

		it('returns empty array when message has no attachments', () => {
			expect(store.prepareAttachments({})).toEqual([])
		})
	})

	describe('startComposerSession from a template message', () => {
		beforeEach(() => {
			vi.spyOn(store, 'startComposerSessionMutation')
		})

		it('copies the attachments of the template message', async () => {
			MessageService.fetchMessage.mockResolvedValue({
				databaseId: 42,
				mailboxId: 1,
				hasHtmlBody: false,
				body: 'Draft body',
				attachments: [{ id: 'att1', messageId: 7, fileName: 'doc.pdf' }],
				inlineAttachments: [],
			})

			await store.startComposerSession({
				data: {
					draftId: 42,
					attachments: [{ id: 'att1', fileName: 'doc.pdf' }],
				},
				templateMessageId: 42,
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({
					attachments: [expect.objectContaining({
						id: 'att1',
						type: 'message-attachment',
						mailboxId: 1,
						uid: 7,
					})],
				}),
			}))
		})

		it('starts without attachments when the template message has none', async () => {
			MessageService.fetchMessage.mockResolvedValue({
				databaseId: 43,
				mailboxId: 1,
				hasHtmlBody: false,
				body: 'Draft body',
				attachments: [],
				inlineAttachments: [],
			})

			await store.startComposerSession({
				data: { draftId: 43 },
				templateMessageId: 43,
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ attachments: [] }),
			}))
		})
	})

	describe('updateAccount', () => {
		it('clears the error flag and syncs mailboxes after a successful settings update', async () => {
			const account = {
				id: 7,
				personalNamespace: '',
				mailboxes: [],
				error: true,
			}
			store.addAccountMutation(account)

			const updatedAccount = { id: 7, personalNamespace: '', mailboxes: [] }
			AccountService.update.mockResolvedValue(updatedAccount)
			MailboxService.fetchAll.mockResolvedValue([])

			await store.updateAccount({ accountId: 7 })

			expect(store.accountsUnmapped[7].error).toBe(false)
			expect(MailboxService.fetchAll).toHaveBeenCalledWith(7, true)
		})
	})

	describe('addInternalAddress', () => {
		it('adds the created internal address', async () => {
			const internalAddress = { id: 3, address: 'example.test', type: 'domain' }
			InternalAddressService.addInternalAddress.mockResolvedValue(internalAddress)

			await store.addInternalAddress({ address: 'example.test', type: 'domain' })

			expect(InternalAddressService.addInternalAddress).toHaveBeenCalledWith('example.test', 'domain')
			expect(store.getInternalAddresses).toEqual([internalAddress])
		})

		it('keeps the internal addresses unchanged if the address already exists', async () => {
			const internalAddress = { id: 3, address: 'example.test', type: 'domain' }
			store.addInternalAddressMutation(internalAddress)
			InternalAddressService.addInternalAddress.mockResolvedValue(null)

			await store.addInternalAddress({ address: 'example.test', type: 'domain' })

			expect(store.getInternalAddresses).toEqual([internalAddress])
		})
	})

	describe('startComposerSession reply-to resolution', () => {
		const account = {
			id: 1,
			emailAddress: 'me@example.com',
			personalNamespace: '',
			mailboxes: [],
			name: 'Me',
		}
		const from = [{ email: 'sender@example.com', label: 'Sender' }]
		const to = [{ email: 'me@example.com', label: 'Me' }]
		const replyTo = [{ email: 'personal-replyto@example.com', label: 'Reply Here' }]

		const makeEnvelope = (overrides = {}) => ({
			databaseId: 42,
			accountId: 1,
			from,
			to,
			replyTo: undefined,
			subject: 'Test subject',
			...overrides,
		})

		const makeOriginal = (overrides = {}) => ({
			databaseId: 42,
			hasHtmlBody: false,
			body: 'Message body',
			replyTo: undefined,
			unsubscribeUrl: null,
			unsubscribeMailto: null,
			...overrides,
		})

		beforeEach(() => {
			store.addAccountMutation(account)
			vi.spyOn(store, 'startComposerSessionMutation')
		})

		it('uses Reply-To instead of From when available', async () => {
			MessageService.fetchMessage.mockResolvedValue(makeOriginal({ replyTo }))

			await store.startComposerSession({
				reply: {
					mode: 'reply',
					data: makeEnvelope({ replyTo }),
				},
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ to: replyTo }),
			}))
		})

		it('uses From when Reply-To is empty', async () => {
			MessageService.fetchMessage.mockResolvedValue(makeOriginal({ replyTo: [] }))

			await store.startComposerSession({
				reply: {
					mode: 'reply',
					data: makeEnvelope(),
				},
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ to: from }),
			}))
		})

		it('uses To for own sent messages (isOwnMessage follow-up)', async () => {
			const ownFrom = [{ email: 'me@example.com', label: 'Me' }]
			MessageService.fetchMessage.mockResolvedValue(makeOriginal())

			await store.startComposerSession({
				reply: {
					mode: 'reply',
					data: makeEnvelope({ from: ownFrom }),
				},
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ to }),
			}))
		})

		it('uses To when followUp flag is set', async () => {
			MessageService.fetchMessage.mockResolvedValue(makeOriginal())

			await store.startComposerSession({
				reply: {
					mode: 'reply',
					data: makeEnvelope(),
					followUp: true,
				},
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ to }),
			}))
		})
	})

	describe('startComposerSession outbox editing', () => {
		it('uses the server-updated message when stopping a scheduled outbox message', async () => {
			vi.spyOn(store, 'startComposerSessionMutation')

			const outboxStore = useOutboxStore()
			const pastSendAt = Math.floor(Date.now() / 1000) - 3600
			const message = {
				id: 42,
				accountId: 1,
				sendAt: pastSendAt,
				to: [],
				subject: 'Past due',
			}
			outboxStore.addMessageMutation({ message })
			OutboxService.updateMessage.mockResolvedValue({
				...message,
				sendAt: undefined,
			})

			await store.startComposerSession({
				type: 'outbox',
				data: { ...message },
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ sendAt: undefined }),
				originalSendAt: pastSendAt,
			}))
		})
	})
})
