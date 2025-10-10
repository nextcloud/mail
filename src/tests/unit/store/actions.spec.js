/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { curry, range, reverse } from 'ramda'

import * as MailboxService from '../../../service/MailboxService.js'
import * as MessageService from '../../../service/MessageService.js'
import * as NotificationService from '../../../service/NotificationService.js'
import { UNIFIED_INBOX_ID, PAGE_SIZE } from '../../../store/constants.js'
import { normalizedEnvelopeListId } from '../../../util/normalization.js'

import { createPinia, setActivePinia } from 'pinia'

import useMainStore from '../../../store/mainStore.js'

jest.mock('../../../service/MailboxService.js')
jest.mock('../../../service/MessageService.js')
jest.mock('../../../service/NotificationService.js')
jest.mock('../../../util/normalization.js', () => ({
	__esModule: true,
	// Supply a default list id ('') to prevent annoying errors
	normalizedEnvelopeListId: jest.fn(() => ''),
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
		jest.clearAllMocks()
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

	it('combines unified inbox even if no inboxes are present', async() => {
		const envelopes = await store.fetchEnvelopes({
			mailboxId: UNIFIED_INBOX_ID,
		})

		expect(envelopes).toEqual([])
	})

	it('creates a unified page from one mailbox', async() => {
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

		store.addEnvelopesMutation = jest.fn()

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

	it('fetches the next individual page', async() => {
		const msgs1 = reverse(range(30, 40))
		const page1 = reverse(range(10, 30))

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
			.toHaveBeenNthCalledWith(1, 13, 11, undefined, 300000, PAGE_SIZE, 'newest')
		expect(store.mailboxes[UNIFIED_INBOX_ID].envelopeLists[''].toSorted()).toEqual(
			[
				// Initial envelopes
				...msgs1.map(mockEnvelope(11)),

				// Fetched page for mailbox 11
				...page1.map(mockEnvelope(11)),
			].map(e => e.databaseId).sort(),
		)
		expect(store.mailboxes[11].envelopeLists[''].toSorted()).toEqual(
			[
				// Initial envelopes
				...msgs1.map(mockEnvelope(11)),

				// Fetched page for mailbox 11
				...page1.map(mockEnvelope(11)),
			].map(e => e.databaseId).sort(),
		)
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
		expect(store.mailboxes[UNIFIED_INBOX_ID].envelopeLists[''].toSorted()).toEqual(
			[
				// Initial envelopes
				...page1.map(mockEnvelope(11)),

				// Envelopes loaded from local state
				...range(30, 50).map(mockEnvelope(11)),
			].map(e => e.databaseId).sort(),
		)
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
		MessageService.fetchEnvelopes.mockImplementation(
			async (
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
				return page2.map(mockEnvelope(11)).filter(e => e.dateInt < cursor).slice(0, limit)
			},
		)

		await store.fetchNextEnvelopePage({
			mailboxId: UNIFIED_INBOX_ID,
			quantity: PAGE_SIZE,
		})

		expect(MessageService.fetchEnvelopes).toHaveBeenCalledTimes(2)
		expect(MessageService.fetchEnvelopes)
			.toHaveBeenNthCalledWith(1, 13, 11, undefined, 300000, PAGE_SIZE, 'newest')
		expect(MessageService.fetchEnvelopes)
			.toHaveBeenNthCalledWith(2, 26, 21, undefined, 600000, PAGE_SIZE, 'newest')
		expect(store.mailboxes[UNIFIED_INBOX_ID].envelopeLists[''].toSorted()).toEqual(
			[
				// Initial envelopes
				...page1.map(mockEnvelope(11)),
				...msgs2.map(mockEnvelope(21)),

				// Fetched page for mailbox 11
				...page2.map(mockEnvelope(11)),
			].map(e => e.databaseId).sort(),
		)
		expect(store.mailboxes[11].envelopeLists[''].toSorted()).toEqual(
			[
				// Initial envelopes
				...page1.map(mockEnvelope(11)),

				// Fetched page for mailbox 11
				...page2.map(mockEnvelope(11)),
			].map(e => e.databaseId).sort(),
		)
		expect(store.mailboxes[21].envelopeLists[''].toSorted())
			.toEqual(msgs2.map(mockEnvelope(21)).map(e => e.databaseId).toSorted())
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

			store.fetchEnvelopes = jest.fn(async () => {})
			store.syncEnvelopes = jest.fn(async () => {})

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

			store.fetchEnvelopes = jest.fn(async () => {})
			store.syncEnvelopes = jest.fn(async () => [{ id: 123 }, { id: 321 }])

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

	it('includes a cache buster if requested', async() => {
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

		store.addEnvelopesMutation = jest.fn()

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
})
