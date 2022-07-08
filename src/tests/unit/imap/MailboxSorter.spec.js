/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
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

import { sortMailboxes } from '../../../imap/MailboxSorter'

describe('mailboxSorter', () => {
	it('sorts ordinary mailboxes', () => {
		const mb1 = {
			name: 'Inbox 1',
			specialUse: [],
		}
		const mb2 = {
			name: 'Inbox 2',
			specialUse: [],
		}
		const mailboxes = [mb2, mb1]

		const sorted = sortMailboxes(mailboxes)

		expect(sorted).toEqual([mb1, mb2])
	})

	it('lists special mailboxes first', () => {
		const mb1 = {
			name: 'Inbox 1',
			specialUse: [],
		}
		const mb2 = {
			name: 'Inbox 2',
			specialUse: ['inbox'],
		}
		const mailboxes = [mb1, mb2]

		const sorted = sortMailboxes(mailboxes)

		expect(sorted).toEqual([mb2, mb1])
	})

	it('sorts equally special mailboxes', () => {
		const mb1 = {
			name: 'Inbox 1',
			specialUse: ['inbox'],
		}
		const mb2 = {
			name: 'Inbox 2',
			specialUse: ['inbox'],
		}
		const mailboxes = [mb1, mb2]

		const sorted = sortMailboxes(mailboxes)

		expect(sorted).toEqual([mb1, mb2])
	})

	it('sorts real-world mailboxes', () => {
		const mb1 = {
			name: 'Drafts',
			specialUse: ['drafts'],
		}
		const mb2 = {
			name: 'Inbox',
			specialUse: ['inbox'],
		}
		const mb3 = {
			name: 'Other 2',
			specialUse: [],
		}
		const mb4 = {
			name: 'Other 1',
			specialUse: [],
		}
		const mb5 = {
			name: 'Sent',
			specialUse: ['sent'],
		}
		const mb6 = {
			name: 'Sent2',
			specialUse: ['sent'],
		}
		const mailboxes = [mb1, mb2, mb3, mb4, mb5, mb6]

		const sorted = sortMailboxes(mailboxes)

		expect(sorted).toEqual([mb2, mb1, mb5, mb6, mb4, mb3])
	})
})
