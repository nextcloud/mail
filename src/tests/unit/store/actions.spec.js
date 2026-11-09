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
import * as TrustedSenderService from '../../../service/TrustedSenderService.js'
import { PAGE_SIZE, TRUSTED_SENDERS_MAX_AGE, UNIFIED_INBOX_ID } from '../../../store/constants.js'
import useMainStore from '../../../store/mainStore.js'
import { normalizedEnvelopeListId } from '../../../util/normalization.js'

vi.mock('../../../service/AccountService.js')
vi.mock('../../../service/InternalAddressService.js')
vi.mock('../../../service/MailboxService.js')
vi.mock('../../../service/MessageService.js')
vi.mock('../../../service/NotificationService.js')
vi.mock('../../../service/TrustedSenderService.js')
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

const fetched = (data, etag = '"list"') => ({ data, etag })

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

		it('uses From for mailing list emails even when Reply-To is set', async () => {
			MessageService.fetchMessage.mockResolvedValue(makeOriginal({
				replyTo,
				unsubscribeUrl: 'https://list.example.com/unsubscribe',
			}))

			await store.startComposerSession({
				reply: {
					mode: 'reply',
					data: makeEnvelope({ replyTo }),
				},
			})

			expect(store.startComposerSessionMutation).toHaveBeenCalledWith(expect.objectContaining({
				data: expect.objectContaining({ to: from }),
			}))
		})

		it('honours Reply-To for regular emails with a personal Reply-To', async () => {
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

	describe('trusted senders', () => {
		const alice = { id: 1, email: 'alice@example.test', type: 'individual' }
		const exampleDomain = { id: 2, email: 'example.test', type: 'domain' }

		it('trusts an individual sender by the exact address', () => {
			store.setTrustedSendersMutation([alice])

			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
			expect(store.isSenderTrusted('ALICE@example.test')).toBe(false)
			expect(store.isSenderTrusted('bob@example.test')).toBe(false)
		})

		it('trusts every sender of a trusted domain, but not of its subdomains', () => {
			store.setTrustedSendersMutation([exampleDomain])

			expect(store.isSenderTrusted('bob@example.test')).toBe(true)
			expect(store.isSenderTrusted('bob@mail.example.test')).toBe(false)
		})

		it('takes the domain after the last at sign, so a quoted local part cannot claim a trusted domain', () => {
			store.setTrustedSendersMutation([exampleDomain])
			expect(store.isSenderTrusted('bob@example.test@other.test')).toBe(false)

			store.setTrustedSendersMutation([{ email: 'other.test', type: 'domain' }])
			expect(store.isSenderTrusted('bob@example.test@other.test')).toBe(true)
		})

		it('never matches a domain for a sender without an at sign', () => {
			store.setTrustedSendersMutation([exampleDomain])

			expect(store.isSenderTrusted('example.test')).toBe(false)
		})

		it('keeps individual and domain entries apart', () => {
			store.setTrustedSendersMutation([{ email: 'alice@example.test', type: 'domain' }, { email: 'example.test', type: 'individual' }])

			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
			expect(store.isSenderTrusted('bob@example.test')).toBe(false)
		})

		it('does not trust a message without a sender', () => {
			store.setTrustedSendersMutation([alice, exampleDomain])

			expect(store.isSenderTrusted(undefined)).toBe(false)
		})

		it('trusts a sender before the server confirms it', async () => {
			let trustedWhileSaving
			TrustedSenderService.trustSender.mockImplementationOnce(async () => {
				trustedWhileSaving = store.isSenderTrusted('alice@example.test')
			})

			await store.trustSender({ email: 'alice@example.test', type: 'individual' })

			expect(trustedWhileSaving).toBe(true)
			expect(TrustedSenderService.trustSender).toHaveBeenCalledWith('alice@example.test', 'individual', true)
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('stops trusting a sender the server rejects', async () => {
			const error = new Error('Request failed with status code 422')
			TrustedSenderService.trustSender.mockRejectedValueOnce(error)

			await expect(store.trustSender({ email: 'alice@example.test', type: 'individual' })).rejects.toBe(error)

			expect(store.trustedSenders).toEqual([])
		})

		it('does not save a sender that is already trusted', async () => {
			store.setTrustedSendersMutation([alice])

			await store.trustSender({ email: 'alice@example.test', type: 'individual' })

			expect(TrustedSenderService.trustSender).not.toHaveBeenCalled()
			expect(store.trustedSenders).toEqual([alice])
		})

		it('stops trusting a sender before the server confirms the removal', async () => {
			const otherDomain = { id: 3, email: 'other.test', type: 'domain' }
			store.setTrustedSendersMutation([alice, otherDomain])
			let trustedWhileRemoving
			TrustedSenderService.trustSender.mockImplementationOnce(async () => {
				trustedWhileRemoving = store.isSenderTrusted('alice@example.test')
			})

			await store.removeTrustedSender(alice)

			expect(trustedWhileRemoving).toBe(false)
			expect(TrustedSenderService.trustSender).toHaveBeenCalledWith('alice@example.test', 'individual', false)
			expect(store.trustedSenders).toEqual([otherDomain])
		})

		it('trusts a sender again when the server could not remove it', async () => {
			store.setTrustedSendersMutation([alice])
			const error = new Error('Request failed with status code 500')
			TrustedSenderService.trustSender.mockRejectedValueOnce(error)

			await expect(store.removeTrustedSender(alice)).rejects.toBe(error)

			expect(store.trustedSenders).toEqual([alice])
		})

		it('trusts nobody while the list is stale', () => {
			store.setTrustedSendersMutation([alice])
			store.setTrustedSendersStaleMutation(true)

			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
		})

		it('trusts again once a stale list is reloaded', async () => {
			store.setTrustedSendersStaleMutation(true)
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([alice]))

			await store.fetchTrustedSenders()

			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('keeps trusting nobody when reloading a stale list fails', async () => {
			store.setTrustedSendersMutation([alice])
			store.setTrustedSendersStaleMutation(true)
			TrustedSenderService.fetchTrustedSenders.mockRejectedValueOnce(new Error('Request failed with status code 500'))

			await expect(store.fetchTrustedSenders()).rejects.toThrow()

			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
		})

		it('trusts again once a reload after a failed one succeeds', async () => {
			store.setTrustedSendersMutation([alice], '"known"')
			store.setTrustedSendersStaleMutation(true)
			TrustedSenderService.fetchTrustedSenders
				.mockRejectedValueOnce(new Error('Request failed with status code 500'))
				.mockResolvedValueOnce(null)

			await expect(store.fetchTrustedSenders()).rejects.toThrow()
			await store.fetchTrustedSenders()

			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		describe('confirmation of the list', () => {
			beforeEach(() => {
				vi.useFakeTimers({ toFake: ['Date'] })
				store.setTrustedSendersMutation([alice], '"known"')
				store.confirmTrustedSendersMutation()
			})

			afterEach(() => {
				vi.useRealTimers()
			})

			it('trusts nobody while reloading a list that wasn\'t confirmed for two sync intervals', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.fetchTrustedSenders.mockReturnValueOnce(request.promise)
				vi.setSystemTime(Date.now() + TRUSTED_SENDERS_MAX_AGE)

				const loading = store.fetchTrustedSenders()
				const trustedWhileLoading = store.isSenderTrusted('alice@example.test')
				request.resolve(null)
				await loading

				expect(trustedWhileLoading).toBe(false)
				expect(store.isSenderTrusted('alice@example.test')).toBe(true)
			})

			it('keeps trusting while reloading a list confirmed within two sync intervals', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.fetchTrustedSenders.mockReturnValueOnce(request.promise)
				vi.setSystemTime(Date.now() + TRUSTED_SENDERS_MAX_AGE - 1)

				const loading = store.fetchTrustedSenders()
				const trustedWhileLoading = store.isSenderTrusted('alice@example.test')
				request.resolve(null)
				await loading

				expect(trustedWhileLoading).toBe(true)
			})

			it('counts an unchanged list as confirmed', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.fetchTrustedSenders
					.mockResolvedValueOnce(null)
					.mockReturnValueOnce(request.promise)
				vi.setSystemTime(Date.now() + TRUSTED_SENDERS_MAX_AGE - 1)
				await store.fetchTrustedSenders()
				vi.setSystemTime(Date.now() + TRUSTED_SENDERS_MAX_AGE - 1)

				const loading = store.fetchTrustedSenders()
				const trustedWhileLoading = store.isSenderTrusted('alice@example.test')
				request.resolve(null)
				await loading

				expect(trustedWhileLoading).toBe(true)
			})

			it('doesn\'t count a failed reload as a confirmation', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.fetchTrustedSenders
					.mockRejectedValueOnce(new Error('Request failed with status code 500'))
					.mockReturnValueOnce(request.promise)
				vi.setSystemTime(Date.now() + TRUSTED_SENDERS_MAX_AGE - 1)
				await expect(store.fetchTrustedSenders()).rejects.toThrow()
				vi.setSystemTime(Date.now() + 1)

				const loading = store.fetchTrustedSenders()
				const trustedWhileLoading = store.isSenderTrusted('alice@example.test')
				request.resolve(null)
				await loading

				expect(trustedWhileLoading).toBe(false)
			})
		})

		it('replaces the trusted senders with the server\'s list', async () => {
			store.setTrustedSendersMutation([alice])
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([exampleDomain]))

			await store.fetchTrustedSenders()

			expect(store.trustedSenders).toEqual([exampleDomain])
		})

		describe.each([
			{
				change: 'trusting',
				listBefore: [],
				act: () => store.trustSender({ email: 'alice@example.test', type: 'individual' }),
				trusted: true,
			},
			{
				change: 'removing',
				listBefore: [alice],
				act: () => store.removeTrustedSender(alice),
				trusted: false,
			},
		])('when $change alice', ({ listBefore, act, trusted }) => {
			beforeEach(() => {
				store.setTrustedSendersMutation(listBefore)
			})

			it('keeps the change when it starts while the list loads', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.trustSender.mockReturnValueOnce(request.promise)
				let changing
				TrustedSenderService.fetchTrustedSenders.mockImplementationOnce(async () => {
					changing = act()
					return fetched(listBefore)
				})

				await store.fetchTrustedSenders()
				request.resolve()
				await changing

				expect(store.isSenderTrusted('alice@example.test')).toBe(trusted)
			})

			it('keeps the change when it is saved while the list loads', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.trustSender.mockReturnValueOnce(request.promise)
				const changing = act()
				TrustedSenderService.fetchTrustedSenders.mockImplementationOnce(async () => {
					request.resolve()
					await changing
					return fetched(listBefore)
				})

				await store.fetchTrustedSenders()

				expect(store.isSenderTrusted('alice@example.test')).toBe(trusted)
			})

			it('keeps the change when the list loads while it is saved', async () => {
				const request = Promise.withResolvers()
				TrustedSenderService.trustSender.mockReturnValueOnce(request.promise)
				TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched(listBefore))

				const changing = act()
				await store.fetchTrustedSenders()
				const trustedWhileSaving = store.isSenderTrusted('alice@example.test')
				request.resolve()
				await changing

				expect(trustedWhileSaving).toBe(trusted)
				expect(store.isSenderTrusted('alice@example.test')).toBe(trusted)
			})
		})

		it('applies the pending changes in the order they were made to a list that loads meanwhile', async () => {
			store.setTrustedSendersMutation([alice])
			const removal = Promise.withResolvers()
			const trusting = Promise.withResolvers()
			TrustedSenderService.trustSender
				.mockReturnValueOnce(removal.promise)
				.mockReturnValueOnce(trusting.promise)
			TrustedSenderService.fetchTrustedSenders
				.mockResolvedValueOnce(fetched([alice]))
				.mockResolvedValueOnce(fetched([]))

			const removing = store.removeTrustedSender(alice)
			const saving = store.trustSender(alice)
			await store.fetchTrustedSenders()
			const trustedWhileBothSave = store.isSenderTrusted('alice@example.test')
			removal.resolve()
			await removing
			await store.fetchTrustedSenders()
			const trustedWhileTrustingSaves = store.isSenderTrusted('alice@example.test')
			trusting.resolve()
			await saving

			expect(trustedWhileBothSave).toBe(true)
			expect(trustedWhileTrustingSaves).toBe(true)
		})

		it('keeps the last change of a sender when an earlier change of it fails', async () => {
			store.setTrustedSendersMutation([alice])
			const firstRemoval = Promise.withResolvers()
			const trusting = Promise.withResolvers()
			const secondRemoval = Promise.withResolvers()
			TrustedSenderService.trustSender
				.mockReturnValueOnce(firstRemoval.promise)
				.mockReturnValueOnce(trusting.promise)
				.mockReturnValueOnce(secondRemoval.promise)

			const removingFirst = store.removeTrustedSender(alice)
			const saving = store.trustSender(alice)
			const removingAgain = store.removeTrustedSender(alice)
			firstRemoval.reject(new Error('Request failed with status code 500'))
			await expect(removingFirst).rejects.toThrow()
			const trustedAfterFailure = store.isSenderTrusted('alice@example.test')
			trusting.resolve()
			secondRemoval.resolve()
			await Promise.all([saving, removingAgain])

			expect(trustedAfterFailure).toBe(false)
			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
		})

		it('doesn\'t apply a rejected change to a list that loads later', async () => {
			TrustedSenderService.trustSender.mockRejectedValueOnce(new Error('Request failed with status code 500'))
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([]))

			await expect(store.trustSender(alice)).rejects.toThrow()
			await store.fetchTrustedSenders()

			expect(store.trustedSenders).toEqual([])
		})

		it('trusts again once a stale list loads while a change is still saving', async () => {
			store.setTrustedSendersMutation([alice])
			const request = Promise.withResolvers()
			TrustedSenderService.trustSender.mockReturnValueOnce(request.promise)
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([alice]))

			const saving = store.trustSender(exampleDomain)
			await store.fetchTrustedSenders({ stale: true })
			const trustedWhileSaving = store.isSenderTrusted('bob@example.test')
			request.resolve()
			await saving

			expect(trustedWhileSaving).toBe(true)
			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledOnce()
		})

		it('trusts nobody until a list reloaded as stale arrives', async () => {
			store.setTrustedSendersMutation([alice])
			const request = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders.mockReturnValueOnce(request.promise)

			const reloading = store.fetchTrustedSenders({ stale: true })
			const trustedWhileReloading = store.isSenderTrusted('alice@example.test')
			request.resolve(fetched([alice]))
			await reloading

			expect(trustedWhileReloading).toBe(false)
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('keeps trusting nobody when a list requested before it went stale arrives', async () => {
			const earlier = Promise.withResolvers()
			const current = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockReturnValueOnce(current.promise)
			const loadingEarlier = store.fetchTrustedSenders()
			const reloading = store.fetchTrustedSenders({ stale: true })

			earlier.resolve(fetched([alice]))
			await loadingEarlier

			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
			current.resolve(fetched([exampleDomain]))
			await reloading
			expect(store.trustedSenders).toEqual([exampleDomain])
			expect(store.isSenderTrusted('bob@example.test')).toBe(true)
		})

		it('keeps trusting nobody when a list requested before it went stale fails', async () => {
			store.setTrustedSendersMutation([alice])
			const earlier = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockReturnValueOnce(new Promise(() => {}))
			const loadingEarlier = store.fetchTrustedSenders()
			store.fetchTrustedSenders({ stale: true })

			earlier.reject(new Error('Network Error'))
			await expect(loadingEarlier).rejects.toThrow()

			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
		})

		it('reloads a stale list again when a trust change voided the response', async () => {
			const bob = { email: 'bob@other.test', type: 'individual' }
			store.setTrustedSendersMutation([alice, exampleDomain])
			TrustedSenderService.fetchTrustedSenders
				.mockImplementationOnce(async () => {
					await store.trustSender(bob)
					return fetched([alice, exampleDomain])
				})
				.mockResolvedValueOnce(fetched([alice, bob]))

			await store.fetchTrustedSenders({ stale: true })

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledTimes(2)
			expect(store.isSenderTrusted('carol@example.test')).toBe(false)
			expect(store.isSenderTrusted('bob@other.test')).toBe(true)
		})

		it('asks only for changes of the loaded list', async () => {
			TrustedSenderService.fetchTrustedSenders
				.mockResolvedValueOnce(fetched([alice], '"first"'))
				.mockResolvedValueOnce(null)

			await store.fetchTrustedSenders()
			await store.fetchTrustedSenders()

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenNthCalledWith(1, null)
			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenNthCalledWith(2, '"first"')
			expect(store.trustedSenders).toEqual([alice])
		})

		it('trusts again when the server confirms a stale list', async () => {
			store.setTrustedSendersMutation([alice], '"known"')
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(null)

			await store.fetchTrustedSenders({ stale: true })

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith('"known"')
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('reloads the whole list after trusting a sender', async () => {
			store.setTrustedSendersMutation([alice], '"known"')
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([alice]))

			await store.trustSender(exampleDomain)
			await store.fetchTrustedSenders()

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith(null)
			expect(store.trustedSenders).toEqual([alice])
		})

		it('reloads the whole list after removing a sender', async () => {
			store.setTrustedSendersMutation([alice, exampleDomain], '"known"')
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([alice, exampleDomain]))

			await store.removeTrustedSender(exampleDomain)
			await store.fetchTrustedSenders()

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith(null)
			expect(store.trustedSenders).toEqual([alice, exampleDomain])
		})

		it('still asks only for changes of the loaded list after saving a listed sender again', async () => {
			store.setTrustedSendersMutation([alice], '"known"')
			store.setTrustedSendersStaleMutation(true)
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(null)

			await store.trustSender(alice)
			await store.fetchTrustedSenders()

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith('"known"')
			expect(store.trustedSenders).toEqual([alice])
		})

		it('still asks only for changes of the loaded list after trusting a sender failed', async () => {
			store.setTrustedSendersMutation([alice], '"known"')
			TrustedSenderService.trustSender.mockRejectedValueOnce(new Error('Request failed with status code 500'))
			TrustedSenderService.fetchTrustedSenders.mockResolvedValueOnce(fetched([alice, exampleDomain]))

			await expect(store.trustSender(exampleDomain)).rejects.toThrow()
			await store.fetchTrustedSenders()

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledExactlyOnceWith('"known"')
			expect(store.trustedSenders).toEqual([alice, exampleDomain])
		})

		it('doesn\'t remember the ETag of a discarded response', async () => {
			store.setTrustedSendersMutation([alice], '"known"')
			TrustedSenderService.fetchTrustedSenders
				.mockImplementationOnce(async () => {
					await store.trustSender(exampleDomain)
					return fetched([alice], '"discarded"')
				})
				.mockResolvedValueOnce(fetched([alice, exampleDomain]))

			await store.fetchTrustedSenders()
			await store.fetchTrustedSenders()

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenNthCalledWith(2, null)
		})

		it('ignores a list that arrives after a newer one', async () => {
			const earlier = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockResolvedValueOnce(fetched([alice], '"newer"'))
			const loadingEarlier = store.fetchTrustedSenders()

			await store.fetchTrustedSenders()
			earlier.resolve(fetched([exampleDomain], '"older"'))
			await loadingEarlier

			expect(store.trustedSenders).toEqual([alice])
			expect(store.trustedSendersETag).toBe('"newer"')
		})

		it('doesn\'t reload a stale list that a newer request already refreshed', async () => {
			const earlier = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockResolvedValueOnce(fetched([alice]))
			const reloading = store.fetchTrustedSenders({ stale: true })

			await store.fetchTrustedSenders()
			earlier.resolve(fetched([exampleDomain]))
			await reloading

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledTimes(2)
			expect(store.trustedSenders).toEqual([alice])
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('doesn\'t keep reloading when stale reloads overlap', async () => {
			const earlier = Promise.withResolvers()
			const current = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockReturnValueOnce(current.promise)
				.mockReturnValue(new Promise(() => {}))
			const reloadingEarlier = store.fetchTrustedSenders({ stale: true })
			const reloading = store.fetchTrustedSenders({ stale: true })

			earlier.resolve(fetched([exampleDomain]))
			await reloadingEarlier
			current.resolve(fetched([alice]))
			await reloading

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledTimes(2)
			expect(store.trustedSenders).toEqual([alice])
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('trusts nobody until the newest request arrives when a stale reload is outdated', async () => {
			store.setTrustedSendersMutation([alice])
			const earlier = Promise.withResolvers()
			const current = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockReturnValueOnce(current.promise)
			const reloading = store.fetchTrustedSenders({ stale: true })
			const loading = store.fetchTrustedSenders()

			earlier.resolve(fetched([alice]))
			await reloading

			expect(store.isSenderTrusted('alice@example.test')).toBe(false)
			current.resolve(fetched([alice]))
			await loading
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('reloads a stale list again when a trust change voided a newer request', async () => {
			const bob = { email: 'bob@other.test', type: 'individual' }
			const earlier = Promise.withResolvers()
			TrustedSenderService.fetchTrustedSenders
				.mockReturnValueOnce(earlier.promise)
				.mockImplementationOnce(async () => {
					await store.trustSender(bob)
					return fetched([alice])
				})
				.mockResolvedValueOnce(fetched([alice, bob]))
			const reloading = store.fetchTrustedSenders({ stale: true })

			await store.fetchTrustedSenders()
			earlier.resolve(fetched([alice]))
			await reloading

			expect(TrustedSenderService.fetchTrustedSenders).toHaveBeenCalledTimes(3)
			expect(store.trustedSenders).toEqual([alice, bob])
			expect(store.isSenderTrusted('alice@example.test')).toBe(true)
		})

		it('keeps a listed sender when trusting it again fails while the list is stale', async () => {
			store.setTrustedSendersMutation([alice])
			store.setTrustedSendersStaleMutation(true)
			TrustedSenderService.trustSender.mockRejectedValueOnce(new Error('Request failed with status code 500'))

			await expect(store.trustSender(alice)).rejects.toThrow()

			expect(store.trustedSenders).toEqual([alice])
		})

		it('saves a sender trusted while the list is stale', async () => {
			store.setTrustedSendersMutation([alice])
			store.setTrustedSendersStaleMutation(true)

			await store.trustSender(alice)

			expect(TrustedSenderService.trustSender).toHaveBeenCalledExactlyOnceWith('alice@example.test', 'individual', true)
			expect(store.trustedSenders).toEqual([alice])
		})

		it('lists a sender once when a failed removal restores it after a reload', async () => {
			store.setTrustedSendersMutation([alice])
			TrustedSenderService.trustSender.mockImplementationOnce(async () => {
				store.setTrustedSendersMutation([alice])
				throw new Error('Request failed with status code 500')
			})

			await expect(store.removeTrustedSender(alice)).rejects.toThrow()

			expect(store.trustedSenders).toEqual([alice])
		})
	})

	describe('internal address refresh', () => {
		const exampleDomain = { id: 3, address: 'example.test', type: 'domain' }
		const bob = { id: 4, address: 'bob@other.test', type: 'individual' }

		it('replaces the internal addresses with the server\'s list', async () => {
			store.addInternalAddressMutation(bob)
			InternalAddressService.fetchInternalAddresses.mockResolvedValueOnce(fetched([exampleDomain]))

			await store.fetchInternalAddresses()

			expect(store.getInternalAddresses).toEqual([exampleDomain])
		})

		it('keeps an internal address added while the list loads', async () => {
			InternalAddressService.addInternalAddress.mockResolvedValueOnce(exampleDomain)
			InternalAddressService.fetchInternalAddresses.mockImplementationOnce(async () => {
				await store.addInternalAddress({ address: 'example.test', type: 'domain' })
				return fetched([])
			})

			await store.fetchInternalAddresses()

			expect(store.getInternalAddresses).toEqual([exampleDomain])
		})

		it('keeps an internal address removed while the list loads', async () => {
			store.addInternalAddressMutation(exampleDomain)
			InternalAddressService.fetchInternalAddresses.mockImplementationOnce(async () => {
				await store.removeInternalAddress(exampleDomain)
				return fetched([exampleDomain])
			})

			await store.fetchInternalAddresses()

			expect(store.getInternalAddresses).toEqual([])
		})

		it('ignores a list that arrives after a newer one', async () => {
			const earlier = Promise.withResolvers()
			InternalAddressService.fetchInternalAddresses
				.mockReturnValueOnce(earlier.promise)
				.mockResolvedValueOnce(fetched([bob], '"newer"'))
			const loadingEarlier = store.fetchInternalAddresses()

			await store.fetchInternalAddresses()
			earlier.resolve(fetched([exampleDomain], '"older"'))
			await loadingEarlier

			expect(store.getInternalAddresses).toEqual([bob])
			expect(store.internalAddressesETag).toBe('"newer"')
		})

		it('asks only for changes of the loaded list', async () => {
			InternalAddressService.fetchInternalAddresses
				.mockResolvedValueOnce(fetched([exampleDomain], '"first"'))
				.mockResolvedValueOnce(null)

			await store.fetchInternalAddresses()
			await store.fetchInternalAddresses()

			expect(InternalAddressService.fetchInternalAddresses).toHaveBeenNthCalledWith(1, null)
			expect(InternalAddressService.fetchInternalAddresses).toHaveBeenNthCalledWith(2, '"first"')
			expect(store.getInternalAddresses).toEqual([exampleDomain])
		})

		it('reloads the whole list after adding an address', async () => {
			store.setInternalAddressesMutation([bob], '"known"')
			InternalAddressService.addInternalAddress.mockResolvedValueOnce(exampleDomain)
			InternalAddressService.fetchInternalAddresses.mockResolvedValueOnce(fetched([bob]))

			await store.addInternalAddress({ address: 'example.test', type: 'domain' })
			await store.fetchInternalAddresses()

			expect(InternalAddressService.fetchInternalAddresses).toHaveBeenCalledExactlyOnceWith(null)
			expect(store.getInternalAddresses).toEqual([bob])
		})

		it('reloads the whole list after removing an address', async () => {
			store.setInternalAddressesMutation([bob, exampleDomain], '"known"')
			InternalAddressService.fetchInternalAddresses.mockResolvedValueOnce(fetched([bob, exampleDomain]))

			await store.removeInternalAddress(exampleDomain)
			await store.fetchInternalAddresses()

			expect(InternalAddressService.fetchInternalAddresses).toHaveBeenCalledExactlyOnceWith(null)
			expect(store.getInternalAddresses).toEqual([exampleDomain, bob])
		})

		it('doesn\'t remember the ETag of a discarded response', async () => {
			store.setInternalAddressesMutation([bob], '"known"')
			InternalAddressService.addInternalAddress.mockResolvedValueOnce(exampleDomain)
			InternalAddressService.fetchInternalAddresses
				.mockImplementationOnce(async () => {
					await store.addInternalAddress({ address: 'example.test', type: 'domain' })
					return fetched([bob], '"discarded"')
				})
				.mockResolvedValueOnce(fetched([bob, exampleDomain]))

			await store.fetchInternalAddresses()
			await store.fetchInternalAddresses()

			expect(InternalAddressService.fetchInternalAddresses).toHaveBeenNthCalledWith(2, null)
		})
	})
})
