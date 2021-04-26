/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import mutations from '../../../store/mutations'
import {
	PRIORITY_INBOX_ID,
	UNIFIED_ACCOUNT_ID,
	UNIFIED_INBOX_ID,
} from '../../../store/constants'

describe('Vuex store mutations', () => {
	it('adds an account with no mailboxes', () => {
		const state = {
			accountList: [],
			accounts: {},
			envelopes: {},
			mailboxes: {},
			tags: {},
		}

		mutations.addAccount(state, {
			accountId: 13,
			id: 13,
			mailboxes: [],
		})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [],
					collapsed: true,
				},
			},
			envelopes: {},
			mailboxes: {},
			tags: {},
		})
	})

	it('adds an account with one level of mailboxes', () => {
		const state = {
			accountList: [],
			accounts: {},
			envelopes: {},
			mailboxes: {},
			tags: {},
		}

		mutations.addAccount(state, {
			accountId: 13,
			id: 13,
			mailboxes: [
				{
					databaseId: 345,
					name: 'INBOX',
					delimiter: '.',
				},
			],
		})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
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
					name: 'INBOX',
					displayName: 'INBOX',
					delimiter: '.',
					envelopeLists: {},
					path: '',
					mailboxes: [],
				},
			},
			tags: {},
		})
	})

	it('adds an account with a personal namespace', () => {
		const state = {
			accountList: [],
			accounts: {},
			envelopes: {},
			mailboxes: {},
			tags: {},
		}

		mutations.addAccount(state, {
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
			personalNamespace: 'INBOX.',
		})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [
						345,
						346,
					],
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
				}
			},
			tags: {},
		})
	})

	it('adds an account with two levels of mailboxes', () => {
		const state = {
			accountList: [],
			accounts: {},
			envelopes: {},
			mailboxes: {},
			tags: {},
		}

		mutations.addAccount(state, {
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
		})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
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
			tags: {},
		})
	})

	it('adds an account with three levels of mailboxes', () => {
		const state = {
			accountList: [],
			accounts: {},
			envelopes: {},
			mailboxes: {},
			tags: {},
		}

		mutations.addAccount(state, {
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
		})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
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
		const state = {
			accountList: [13],
			accounts: {
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
				}
			},
			tags: {},
		}

		mutations.addMailbox(
			state,
			{
				account,
				mailbox: {
					databaseId: 346,
					name: 'Brchive',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				}
			})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
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
		const state = {
			accountList: [13],
			accounts: {
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
				}
			},
			tags: {},
		}

		mutations.addMailbox(
			state,
			{
				account,
				mailbox: {
					databaseId: 346,
					name: 'Archive.2020',
					delimiter: '.',
					specialUse: ['archive'],
					specialRole: 'archive',
				}
			})

		expect(state).to.deep.equal({
			accountList: [13],
			accounts: {
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
			tags: {},
		})
	})

	it('removes a mailbox', () => {
		const state = {
			accounts: {
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
			tags: {},
		}

		mutations.removeMailbox(state, {
			id: 27,
		})

		expect(state).to.deep.equal({
			accounts: {
				13: {
					accountId: 13,
					id: 13,
					mailboxes: [],
				},
			},
			mailboxes: {},
			tags: {},
		})
	})

	it('removes a sub-mailbox', () => {
		const state = {
			accounts: {
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
			tags: {},
		}

		mutations.removeMailbox(state, {
			id: 28,
		})

		expect(state).to.deep.equal({
			accounts: {
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
			tags: {},
		})
	})

	it('adds envelopes', () => {
		const state = {
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
			tags: {},
		}

		mutations.addEnvelope(state, {
			query: undefined,
			envelope: {
				mailboxId: 27,
				databaseId: 12345,
				id: 123,
				subject: 'henlo',
				uid: 321,
			},
		})

		expect(state).to.deep.equal({
			accounts: {
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
			tags: {},
		})
	})

	it('adds envelopes with overlapping timestamps', () => {
		const state = {
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
			tags: {},
		}

		mutations.addEnvelope(state, {
			query: undefined,
			envelope: {
				mailboxId: 27,
				databaseId: 12345,
				id: 123,
				subject: 'henlo',
				uid: 321,
			},
		})
		mutations.addEnvelope(state, {
			query: undefined,
			envelope: {
				mailboxId: 27,
				databaseId: 12346,
				id: 124,
				subject: 'henlo 2',
				uid: 322,
			},
		})

		expect(state).to.deep.equal({
			accounts: {
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
				12346: {
					mailboxId: 27,
					databaseId: 12346,
					id: 124,
					subject: 'henlo 2',
					uid: 322,
					tags: [],
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
			tags: {},
		})
	})

	it('adds new envelopes to the unified inbox as well', () => {
		const state = {
			accounts: {
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
			tags: {},
		}

		mutations.addEnvelope(state, {
			query: undefined,
			envelope: {
				mailboxId: 27,
				databaseId: 12345,
				subject: 'henlo',
				uid: 321,
			},
		})

		expect(state).to.deep.equal({
			accounts: {
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
			tags: {},
		})
	})

	it('removes an envelope', () => {
		const state = {
			accounts: {
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
			tags: {},
		}

		mutations.removeEnvelope(state, {
			id: 12345,
		})

		expect(state).to.deep.equal({
			accounts: {
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
			tags: {},
		})
	})

	it('adds a thread', () => {
		const envelope = {
			databaseId: 123,
			mailboxId: 27,
			uid: 12345,
		}
		const state = {
			mailboxes: {
				27: {
					databaseId: 27,
					accountId: 1,
				},
			},
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			tags: {},
		}

		mutations.addEnvelopeThread(state, {
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
				}
			],
		})

		expect(state).to.deep.equal({
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
			tags: {},
		})
	})

	it('normalizes tags from envelopes', () => {
		const state = {
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
			tags: {},
		}

		mutations.addEnvelope(state, {
			query: undefined,
			envelope: {
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
			},
		})

		expect(state).to.deep.equal({
			accounts: {
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
		const state = {
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
			tags: {},
		}

		mutations.addEnvelopeThread(state, {
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

		expect(state).to.deep.equal({
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
		const state = {
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
			tags: {},
		}

		mutations.updateEnvelope(state, {
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

		expect(state).to.deep.equal({
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
		const state = {
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			tags: {
				[tag.id]: tag,
			},
		}

		mutations.removeEnvelopeTag(state, {
			envelope,
			tagId: tag.id,
		})

		expect(state).to.deep.equal({
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
		const state = {
			envelopes: {
				[envelope.databaseId]: envelope,
			},
			tags: {
				[tag.id]: tag,
			},
		}

		mutations.addEnvelopeTag(state, {
			envelope,
			tagId: tag.id,
		})

		expect(state).to.deep.equal({
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
		const state = {
			tags: {},
		}

		mutations.addTag(state, {
			tag: {
				id: 1,
				userId: 'user',
				displayName: 'Important',
				imapLabel: '$label1',
				color: '#ffffff',
				isDefaultTag: true,
			},
		})

		expect(state).to.deep.equal({
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
})
