/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	PRIORITY_INBOX_ID,
	UNIFIED_ACCOUNT_ID,
	UNIFIED_INBOX_ID,
} from '../../../store/constants.js'

import { setActivePinia } from 'pinia'
import { createTestingPinia } from '@pinia/testing'
import useMainStore from '../../../store/mainStore.js'

describe('Pinia store mutations', () => {
	let store

	beforeEach(() => {
		setActivePinia(createTestingPinia({ stubActions: false }))
		store = useMainStore()
		store.$patch({
			accountList: [],
			accounts: {},
			envelopes: {},
			mailboxes: {},
			tagList: [],
			tags: {},
		})
	})

	it('adds an account with no mailboxes', () => {
		store.addAccountMutation({
			accountId: 13,
			id: 13,
			mailboxes: [],
			aliases: [],
		})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [],
					aliases: [],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {},
			tagList: [],
			tags: {},
		})
	})

	it('adds an account with one level of mailboxes', () => {
		store.addAccountMutation({
			accountId: 13,
			id: 13,
			mailboxes: [
				{
					databaseId: 345,
					name: 'INBOX',
					delimiter: '.',
				},
			],
			aliases: [],
		})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
					],
					aliases: [],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'INBOX',
					displayName: 'INBOX',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds an account with a personal namespace', () => {
		store.addAccountMutation({
			accountId: 13,
			id: 13,
			mailboxes: [
				{
					databaseId: 345,
					name: 'INBOX',
					delimiter: '.',
					specialUse: ['inbox'],
					specialRole: 'inbox',
				},
				{
					databaseId: 346,
					name: 'INBOX.Sent',
					delimiter: '.',
					specialUse: ['sent'],
					specialRole: 'sent',
				},
			],
			aliases: [],
			personalNamespace: 'INBOX.',
		})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
						346,
					],
					aliases: [],
					collapsed: true,
					personalNamespace: 'INBOX.',
				},
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'INBOX',
					displayName: 'INBOX',
					specialUse: ['inbox'],
					specialRole: 'inbox',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					mailboxes: [],
				},
				346: {
					accountId: 13,
					databaseId: 346,
					name: 'INBOX.Sent',
					displayName: 'Sent',
					specialUse: ['sent'],
					specialRole: 'sent',
					delimiter: '.',
					envelopeLists: {},
					path: 'INBOX.',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds an account with two levels of mailboxes', () => {
		store.addAccountMutation({
			accountId: 13,
			id: 13,
			mailboxes: [
				{
					databaseId: 345,
					name: 'Archive',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				},
				{
					databaseId: 346,
					name: 'Archive.2020',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				},
			],
			aliases: [],
		})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
					],
					aliases: [],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'Archive',
					displayName: 'Archive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [
						346,
					],
				},
				346: {
					accountId: 13,
					databaseId: 346,
					name: 'Archive.2020',
					displayName: '2020',
					delimiter: '.',
					envelopeLists: {},
					path: 'Archive',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds an account with three levels of mailboxes', () => {
		store.addAccountMutation({
			accountId: 13,
			id: 13,
			mailboxes: [
				{
					databaseId: 345,
					name: 'Archive',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				},
				{
					databaseId: 346,
					name: 'Archive.2020',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				},
				{
					databaseId: 347,
					name: 'Archive.2020.08',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				},
			],
			aliases: [],
		})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
					],
					aliases: [],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'Archive',
					displayName: 'Archive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [
						346,
					],
				},
				346: {
					accountId: 13,
					databaseId: 346,
					name: 'Archive.2020',
					displayName: '2020',
					delimiter: '.',
					envelopeLists: {},
					path: 'Archive',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [
						347,
					],
				},
				347: {
					accountId: 13,
					databaseId: 347,
					name: 'Archive.2020.08',
					displayName: '08',
					delimiter: '.',
					envelopeLists: {},
					path: 'Archive.2020',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds a top level mailbox', () => {
		const account = {
			accountId: 13,
			id: 13,
			mailboxes: [
				345,
			],
			collapsed: true,
		}
		store.$patch({
			accountList: [13],
			accountsUnmapped: {
				13: account,
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'Archive',
					displayName: 'Archive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})

		store.addMailboxMutation(
			{
				account,
				mailbox: {
					databaseId: 346,
					name: 'Brchive',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				},
			})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
						346,
					],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'Archive',
					displayName: 'Archive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
				346: {
					accountId: 13,
					databaseId: 346,
					name: 'Brchive',
					displayName: 'Brchive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds a sub-mailbox', () => {
		const account = {
			accountId: 13,
			id: 13,
			mailboxes: [
				345,
			],
			collapsed: true,
		}
		store.$patch({
			accountList: [13],
			accountsUnmapped: {
				13: account,
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'Archive',
					displayName: 'Archive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})

		store.addMailboxMutation({
			account,
			mailbox: {
				databaseId: 346,
				name: 'Archive.2020',
				delimiter: '.',
				specialUse: ['archive'],
				specialRole: 'archive',
			},
		})

		expect(store).toMatchObject({
			accountList: [13],
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
					],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {
				345: {
					accountId: 13,
					databaseId: 345,
					name: 'Archive',
					displayName: 'Archive',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [
						346,
					],
				},
				346: {
					accountId: 13,
					databaseId: 346,
					name: 'Archive.2020',
					displayName: '2020',
					delimiter: '.',
					envelopeLists: {},
					path: 'Archive',
					specialUse: ['archive'],
					specialRole: 'archive',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('removes a mailbox', () => {
		store.$patch({
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [27],
				},
			},
			mailboxes: {
				27: {
					accountId: 13,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})

		store.removeMailboxMutation({
			id: 27,
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [],
				},
			},
			mailboxes: {},
			tagList: [],
			tags: {},
		})
	})

	it('removes a sub-mailbox', () => {
		store.$patch({
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [27],
				},
			},
			mailboxes: {
				27: {
					accountId: 13,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					mailboxes: [28],
				},
				28: {
					accountId: 13,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})

		store.removeMailboxMutation({
			id: 28,
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [27],
				},
			},
			mailboxes: {
				27: {
					accountId: 13,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					mailboxes: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds envelopes', () => {
		store.$patch({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})

		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12345,
				id: 123,
				subject: 'henlo',
				uid: 321,
			}],
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {
				12345: {
					mailboxId: 27,
					databaseId: 12345,
					uid: 321,
					id: 123,
					subject: 'henlo',
					tags: [],
				},
			},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {
						'': [12345],
					},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})
	})

	it('adds envelopes with overlapping timestamps', () => {
		store.$patch({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})

		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12345,
				id: 123,
				subject: 'henlo',
				uid: 321,
				threadRootId: '123-456-789',
			}],
		})
		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12346,
				id: 124,
				subject: 'henlo 2',
				uid: 322,
				threadRootId: '234-567-890',
			}],
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {
				12345: {
					mailboxId: 27,
					databaseId: 12345,
					uid: 321,
					id: 123,
					subject: 'henlo',
					tags: [],
					threadRootId: '123-456-789',
				},
				12346: {
					mailboxId: 27,
					databaseId: 12346,
					id: 124,
					subject: 'henlo 2',
					uid: 322,
					tags: [],
					threadRootId: '234-567-890',
				},
			},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {
						'': [12345, 12346],
					},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})
	})

	it('adds new envelopes to the unified inbox as well', () => {
		store.$patch({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [UNIFIED_INBOX_ID],
				},
			},
			envelopes: {},
			mailboxes: {
				27: {
					name: 'INBOX',
					databaseId: 27,
					accountId: 2,
					envelopeLists: {},
					specialRole: 'inbox',
				},
				[UNIFIED_INBOX_ID]: {
					specialRole: 'inbox',
					envelopeLists: {},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})

		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12345,
				subject: 'henlo',
				uid: 321,
			}],
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [UNIFIED_INBOX_ID],
				},
			},
			envelopes: {
				12345: {
					databaseId: 12345,
					mailboxId: 27,
					uid: 321,
					subject: 'henlo',
					tags: [],
				},
			},
			mailboxes: {
				27: {
					name: 'INBOX',
					databaseId: 27,
					accountId: 2,
					specialRole: 'inbox',
					envelopeLists: {
						'': [12345],
					},
				},
				[UNIFIED_INBOX_ID]: {
					specialRole: 'inbox',
					envelopeLists: {
						'': [12345],
					},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})
	})

	it('removes an envelope', () => {
		store.$patch({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [UNIFIED_INBOX_ID, PRIORITY_INBOX_ID],
				},
			},
			envelopes: {
				12345: {
					mailboxId: 27,
					id: 123,
					uid: 12345,
				},
				12346: {
					mailboxId: 27,
					id: 123,
					uid: 12345,
					thread: [12345, 12346],
				},
			},
			mailboxes: {
				27: {
					specialUse: ['inbox'],
					specialRole: 'inbox',
					envelopeLists: {
						'': [12345],
					},
				},
				[UNIFIED_INBOX_ID]: {
					id: UNIFIED_INBOX_ID,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					envelopeLists: {
						'': [12345],
					},
				},
				[PRIORITY_INBOX_ID]: {
					id: PRIORITY_INBOX_ID,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					envelopeLists: {
						'is:starred not:important': [12345],
					},
				},
			},
			tagList: [],
			tags: {},
		})

		store.removeEnvelopeMutation({
			id: 12345,
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [UNIFIED_INBOX_ID, PRIORITY_INBOX_ID],
				},
			},
			envelopes: {
				12346: {
					mailboxId: 27,
					id: 123,
					uid: 12345,
					thread: [12346],
				},
			},
			mailboxes: {
				27: {
					specialUse: ['inbox'],
					specialRole: 'inbox',
					envelopeLists: {
						'': [],
					},
				},
				[UNIFIED_INBOX_ID]: {
					id: UNIFIED_INBOX_ID,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					envelopeLists: {
						'': [],
					},
				},
				[PRIORITY_INBOX_ID]: {
					id: PRIORITY_INBOX_ID,
					specialUse: ['inbox'],
					specialRole: 'inbox',
					envelopeLists: {
						'is:starred not:important': [],
					},
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('adds a thread', () => {
		const envelope = {
			databaseId: 123,
			mailboxId: 27,
			uid: 12345,
		}
		store.$patch({
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			tagList: [],
			tags: {},
		})

		store.addEnvelopeThreadMutation({
			id: 123,
			thread: [
				{
					databaseId: 122,
					mailboxId: 27,
					uid: 12344,
				},
				{
					databaseId: 123,
					mailboxId: 27,
					uid: 12345,
				},
				{
					databaseId: 124,
					mailboxId: 27,
					uid: 12346,
				},
			],
		})

		expect(store).toMatchObject({
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				122: {
					databaseId: 122,
					mailboxId: 27,
					accountId: 1,
					uid: 12344,
					tags: [],
				},
				123: {
					databaseId: 123,
					mailboxId: 27,
					accountId: 1,
					uid: 12345,
					thread: [
						122,
						123,
						124,
					],
					tags: [],
				},
				124: {
					databaseId: 124,
					mailboxId: 27,
					accountId: 1,
					uid: 12346,
					tags: [],
				},
			},
			tagList: [],
			tags: {},
		})
	})

	it('normalizes tags from envelopes', () => {
		store.$patch({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})

		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12345,
				id: 123,
				subject: 'henlo',
				uid: 321,
				tags: {
					1: {
						id: 1,
						userId: 'user',
						displayName: 'Important',
						imapLabel: '$label1',
						color: '#ffffff',
						isDefaultTag: true,
					},
				},
			}],
		})

		expect(store).toMatchObject({
			accountsUnmapped: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {
				12345: {
					mailboxId: 27,
					databaseId: 12345,
					uid: 321,
					id: 123,
					subject: 'henlo',
					tags: [1],
				},
			},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {
						'': [12345],
					},
				},
			},
			tagList: [
				1,
			],
			tags: {
				1: {
					id: 1,
					userId: 'user',
					displayName: 'Important',
					imapLabel: '$label1',
					color: '#ffffff',
					isDefaultTag: true,
				},
			},
			preferences: { 'sort-order': 'newest' },
		})
	})

	it('normalizes tags from envelope threads', () => {
		const tag = {
			id: 1,
			userId: 'user',
			displayName: 'Important',
			imapLabel: '$label1',
			color: '#ffffff',
			isDefaultTag: true,
		}
		const envelope = {
			databaseId: 123,
			mailboxId: 27,
			uid: 12345,
		}
		store.$patch({
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			// State contains old version of envelope with no label
			tagList: [],
			tags: {},
		})

		store.addEnvelopeThreadMutation({
			id: 123,
			thread: [
				{
					databaseId: 122,
					mailboxId: 27,
					uid: 12344,
					tags: {
						$label1: tag,
					},
				},
				{
					databaseId: 123,
					mailboxId: 27,
					uid: 12345,
					tags: {
						$label1: tag,
					},
				},
			],
		})

		expect(store).toMatchObject({
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				122: {
					databaseId: 122,
					mailboxId: 27,
					accountId: 1,
					uid: 12344,
					tags: [1],
				},
				123: {
					databaseId: 123,
					mailboxId: 27,
					accountId: 1,
					uid: 12345,
					thread: [
						122,
						123,
					],
					tags: [1],
				},
			},
			tagList: [
				1,
			],
			tags: {
				1: {
					id: 1,
					userId: 'user',
					displayName: 'Important',
					imapLabel: '$label1',
					color: '#ffffff',
					isDefaultTag: true,
				},
			},
		})
	})

	it('normalizes tags from updated envelopes', () => {
		const envelope = {
			databaseId: 123,
			mailboxId: 27,
			uid: 12345,
			accountId: 1,
		}
		store.$patch({
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			// State contains old version of envelope with no label
			tagList: [],
			tags: {},
		})

		store.updateEnvelopeMutation({
			envelope: {
				...envelope,
				tags: {
					$label1: {
						id: 1,
						userId: 'user',
						displayName: 'Important',
						imapLabel: '$label1',
						color: '#ffffff',
						isDefaultTag: true,
					},
				},
			},
		})

		expect(store).toMatchObject({
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				123: {
					databaseId: 123,
					mailboxId: 27,
					accountId: 1,
					uid: 12345,
					flags: undefined,
					tags: [1],
				},
			},
			tagList: [
				1,
			],
			tags: {
				1: {
					id: 1,
					userId: 'user',
					displayName: 'Important',
					imapLabel: '$label1',
					color: '#ffffff',
					isDefaultTag: true,
				},
			},
		})
	})

	it('removes a tag from an envelope', () => {
		const tag = {
			id: 1,
			userId: 'user',
			displayName: 'Important',
			imapLabel: '$label1',
			color: '#ffffff',
			isDefaultTag: true,
		}
		const envelope = {
			mailboxId: 27,
			databaseId: 12345,
			id: 123,
			subject: 'henlo',
			uid: 321,
			tags: [tag.id],
		}
		store.$patch({
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			tags: {
				[tag.id]: tag,
			},
		})

		store.removeEnvelopeTagMutation({
			envelope,
			tagId: tag.id,
		})

		expect(store).toMatchObject({
			envelopes: {
				12345: {
					mailboxId: 27,
					databaseId: 12345,
					uid: 321,
					id: 123,
					subject: 'henlo',
					tags: [],
				},
			},
			tags: {
				[tag.id]: tag,
			},
		})
	})

	it('adds a tag to an envelope', () => {
		const tag = {
			id: 1,
			userId: 'user',
			displayName: 'Important',
			imapLabel: '$label1',
			color: '#ffffff',
			isDefaultTag: true,
		}
		const envelope = {
			mailboxId: 27,
			databaseId: 12345,
			id: 123,
			subject: 'henlo',
			uid: 321,
			tags: [],
		}
		store.$patch({
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			tags: {
				[tag.id]: tag,
			},
		})

		store.addEnvelopeTagMutation({
			envelope,
			tagId: tag.id,
		})

		expect(store).toMatchObject({
			envelopes: {
				12345: {
					mailboxId: 27,
					databaseId: 12345,
					uid: 321,
					id: 123,
					subject: 'henlo',
					tags: [1],
				},
			},
			tags: {
				[tag.id]: tag,
			},
		})
	})

	it('adds a global tag', () => {
		store.$patch({
			tagList: [],
			tags: {},
		})

		store.addTagMutation({
			tag: {
				id: 1,
				userId: 'user',
				displayName: 'Important',
				imapLabel: '$label1',
				color: '#ffffff',
				isDefaultTag: true,
			},
		})

		expect(store).toMatchObject({
			tagList: [
				1,
			],
			tags: {
				1: {
					id: 1,
					userId: 'user',
					displayName: 'Important',
					imapLabel: '$label1',
					color: '#ffffff',
					isDefaultTag: true,
				},
			},
		})
	})

	it('replace envelope for existing thread root id', () => {
		store.$patch({
			accounts: {
				[UNIFIED_ACCOUNT_ID]: {
					accountId: UNIFIED_ACCOUNT_ID,
					id: UNIFIED_ACCOUNT_ID,
					mailboxes: [],
				},
			},
			envelopes: {},
			mailboxes: {
				27: {
					name: 'INBOX',
					accountId: 13,
					envelopeLists: {},
				},
			},
			tagList: [],
			tags: {},
			preferences: { 'sort-order': 'newest' },
		})

		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12345,
				id: 123,
				subject: 'henlo',
				uid: 321,
				threadRootId: '123-456-789',
			}],
		})

		expect(store.mailboxes[27].envelopeLists[''].length).toEqual(1)

		store.addEnvelopesMutation({
			query: undefined,
			envelopes: [{
				mailboxId: 27,
				databaseId: 12347,
				id: 234,
				subject: 'henlo',
				uid: 432,
				threadRootId: '123-456-789',
			}],
		})

		expect(store.mailboxes[27].envelopeLists[''].length).toEqual(1)
	})
})
