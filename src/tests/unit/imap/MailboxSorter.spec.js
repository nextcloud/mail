/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { sortMailboxes } from '../../../imap/MailboxSorter.js'

/**
 * Every ordering of a list, to check that a sort does not depend on its input order
 *
 * @param {Array} list the items to arrange
 * @return {Array[]} all permutations of list
 */
function permutations(list) {
	if (list.length <= 1) {
		return [list]
	}
	return list.flatMap((item, i) => permutations([...list.slice(0, i), ...list.slice(i + 1)])
		.map((rest) => [item, ...rest]))
}

describe('mailboxSorter', () => {
	it('sorts ordinary mailboxes', () => {
		const mb1 = {
			name: 'Inbox 1',
			specialUse: [],
			databaseId: 1,
		}
		const mb2 = {
			name: 'Inbox 2',
			specialUse: [],
			databaseId: 2,
		}
		const mailboxes = [mb2, mb1]

		const account = {
			snoozeMailboxId: 0,
		}

		const sorted = sortMailboxes(mailboxes, account)

		expect(sorted).toEqual([mb1, mb2])
	})

	it('puts inbox before all', () => {
		const mbAll = {
			name: 'All emails',
			specialUse: ['all'],
			databaseId: 1,
		}
		const inbox = {
			name: 'INBOX',
			specialUse: ['inbox'],
			databaseId: 2,
		}
		const mailboxes = [inbox, mbAll]

		const account = {
			snoozeMailboxId: 0,
		}

		const sorted = sortMailboxes(mailboxes, account)

		expect(sorted).toEqual([inbox, mbAll])
	})

	it('handles unknown special use', () => {
		const specialMb = {
			name: 'Special emails',
			specialUse: ['special'],
			databaseId: 1,
		}
		const inbox = {
			name: 'INBOX',
			specialUse: ['inbox'],
			databaseId: 2,
		}
		const mailboxes = [inbox, specialMb]

		const account = {
			snoozeMailboxId: 0,
		}

		const sorted = sortMailboxes(mailboxes, account)

		expect(sorted).toEqual([inbox, specialMb])
	})

	it('lists special mailboxes first', () => {
		const mb1 = {
			name: 'Inbox 1',
			specialUse: [],
			databaseId: 1,
		}
		const mb2 = {
			name: 'Inbox 2',
			specialUse: ['inbox'],
			databaseId: 2,
		}
		const mailboxes = [mb1, mb2]

		const account = {
			snoozeMailboxId: 0,
		}

		const sorted = sortMailboxes(mailboxes, account)

		expect(sorted).toEqual([mb2, mb1])
	})

	it('sorts equally special mailboxes', () => {
		const mb1 = {
			name: 'Inbox 1',
			specialUse: ['inbox'],
			databaseId: 1,
		}
		const mb2 = {
			name: 'Inbox 2',
			specialUse: ['inbox'],
			databaseId: 2,
		}
		const mailboxes = [mb1, mb2]

		const account = {
			snoozeMailboxId: 0,
		}

		const sorted = sortMailboxes(mailboxes, account)

		expect(sorted).toEqual([mb1, mb2])
	})

	it('sorts real-world mailboxes', () => {
		const mb1 = {
			name: 'Drafts',
			specialUse: ['drafts'],
			databaseId: 2,
		}
		const mb2 = {
			name: 'Inbox',
			specialUse: ['inbox'],
			databaseId: 1,
		}
		const mb3 = {
			name: 'Other 2',
			specialUse: [],
			databaseId: 3,
		}
		const mb4 = {
			name: 'Other 1',
			specialUse: [],
			databaseId: 4,
		}
		const mb5 = {
			name: 'Sent',
			specialUse: ['sent'],
			databaseId: 5,
		}
		const mb6 = {
			name: 'Sent2',
			specialUse: ['sent'],
			databaseId: 6,
		}
		const mb7 = {
			name: 'Snoozed',
			specialUse: [],
			databaseId: 7,
		}
		const mailboxes = [mb1, mb2, mb3, mb4, mb5, mb6, mb7]

		const account = {
			snoozeMailboxId: 7,
		}

		const sorted = sortMailboxes(mailboxes, account)

		expect(sorted).toEqual([mb2, mb1, mb5, mb6, mb7, mb4, mb3])
	})

	it('puts the snoozed mailbox first whatever order the mailboxes arrive in', () => {
		const inbox = {
			name: 'INBOX',
			specialUse: ['inbox'],
			databaseId: 1,
		}
		const snoozed = {
			name: 'Snoozed',
			specialUse: [],
			databaseId: 2,
		}
		// Names that sort before and after "Snoozed", in the same order in every locale
		const first = {
			name: 'Autos',
			specialUse: [],
			databaseId: 3,
		}
		const second = {
			name: 'Bills',
			specialUse: [],
			databaseId: 4,
		}
		const third = {
			name: 'Recipes',
			specialUse: [],
			databaseId: 5,
		}
		const last = {
			name: 'Taxes',
			specialUse: [],
			databaseId: 6,
		}

		const account = {
			snoozeMailboxId: 2,
		}

		for (const mailboxes of permutations([inbox, snoozed, first, second, third, last])) {
			const sorted = sortMailboxes(mailboxes, account)

			expect(sorted).toEqual([inbox, snoozed, first, second, third, last])
		}
	})

	it('keeps a snoozed mailbox inside an ordinary mailbox after its parent', () => {
		const before = {
			name: 'Autos',
			specialUse: [],
			databaseId: 1,
			delimiter: '/',
		}
		const parent = {
			name: 'Projects',
			specialUse: [],
			databaseId: 2,
			delimiter: '/',
		}
		const snoozed = {
			name: 'Projects/Snoozed',
			specialUse: [],
			databaseId: 3,
			delimiter: '/',
		}
		const after = {
			name: 'Taxes',
			specialUse: [],
			databaseId: 4,
			delimiter: '/',
		}

		const account = {
			snoozeMailboxId: 3,
		}

		for (const mailboxes of permutations([before, parent, snoozed, after])) {
			const sorted = sortMailboxes(mailboxes, account)

			expect(sorted).toEqual([before, parent, snoozed, after])
		}
	})

	it('puts a snoozed mailbox inside a special-use mailbox first', () => {
		const inbox = {
			name: 'INBOX',
			specialUse: ['inbox'],
			databaseId: 1,
			delimiter: '.',
		}
		const sibling = {
			name: 'INBOX.Autos',
			specialUse: [],
			databaseId: 2,
			delimiter: '.',
		}
		const snoozed = {
			name: 'INBOX.Snoozed',
			specialUse: [],
			databaseId: 3,
			delimiter: '.',
		}

		const account = {
			snoozeMailboxId: 3,
		}

		for (const mailboxes of permutations([inbox, sibling, snoozed])) {
			const sorted = sortMailboxes(mailboxes, account)

			expect(sorted).toEqual([inbox, snoozed, sibling])
		}
	})

	it('keeps a deeper nested snoozed mailbox after its ordinary parent', () => {
		// The parent is the mailbox named up to the last delimiter (INBOX.Projects), not the first (INBOX)
		const inbox = {
			name: 'INBOX',
			specialUse: ['inbox'],
			databaseId: 1,
			delimiter: '.',
		}
		const sibling = {
			name: 'INBOX.Autos',
			specialUse: [],
			databaseId: 2,
			delimiter: '.',
		}
		const parent = {
			name: 'INBOX.Projects',
			specialUse: [],
			databaseId: 3,
			delimiter: '.',
		}
		const snoozed = {
			name: 'INBOX.Projects.Snoozed',
			specialUse: [],
			databaseId: 4,
			delimiter: '.',
		}

		const account = {
			snoozeMailboxId: 4,
		}

		for (const mailboxes of permutations([inbox, sibling, parent, snoozed])) {
			const sorted = sortMailboxes(mailboxes, account)

			expect(sorted).toEqual([inbox, sibling, parent, snoozed])
		}
	})
})
