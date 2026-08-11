/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { sortMailboxes } from '../../../imap/MailboxSorter.js'

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
})
