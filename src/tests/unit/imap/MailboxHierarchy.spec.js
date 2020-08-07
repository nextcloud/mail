/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import { buildMailboxHierarchy } from '../../../imap/MailboxHierarchy'

describe('mailboxHierarchyBuilder', () => {
	it('handles empty collections', () => {
		const mailboxes = []

		const hierarchy = buildMailboxHierarchy(mailboxes)

		expect(hierarchy).to.deep.equal(mailboxes)
	})

	it('builds a flat hierarchy', () => {
		const mb1 = {
			id: btoa('INBOX'),
			delimiter: '.',
		}
		const mb2 = {
			id: btoa('Sent'),
			delimiter: '.',
		}
		const mailboxes = [mb1, mb2]

		const hierarchy = buildMailboxHierarchy(mailboxes)

		expect(hierarchy).to.deep.equal([
			{
				id: btoa('INBOX'),
				delimiter: '.',
				folders: [],
			},
			{
				id: btoa('Sent'),
				delimiter: '.',
				folders: [],
			},
		])
	})

	it('builds a nested hierarchy with one level', () => {
		const mb1 = {
			id: btoa('Archive'),
			delimiter: '.',
		}
		const mb2 = {
			id: btoa('Archive.Sent'),
			delimiter: '.',
		}
		const mailboxes = [mb1, mb2]

		const hierarchy = buildMailboxHierarchy(mailboxes)

		expect(hierarchy).to.deep.equal([
			{
				id: btoa('Archive'),
				delimiter: '.',
				folders: [
					{
						id: btoa('Archive.Sent'),
						delimiter: '.',
						folders: [],
					},
				],
			},
		])
	})

	it('builds a nested hierarchy with two levels', () => {
		const mb1 = {
			id: btoa('Archive'),
			delimiter: '.',
		}
		const mb2 = {
			id: btoa('Archive.Sent'),
			delimiter: '.',
		}
		const mb3 = {
			id: btoa('Archive.Sent.Old'),
			delimiter: '.',
		}
		const mailboxes = [mb1, mb2, mb3]

		const hierarchy = buildMailboxHierarchy(mailboxes)

		expect(hierarchy).to.deep.equal([
			{
				id: btoa('Archive'),
				delimiter: '.',
				folders: [
					{
						id: btoa('Archive.Sent'),
						delimiter: '.',
						folders: [],
					},
					{
						id: btoa('Archive.Sent.Old'),
						delimiter: '.',
						folders: [],
					},
				],
			},
		])
	})

	it('does not use the flagged inbox as subfolder of inbox', () => {
		const mb1 = {
			id: btoa('INBOX'),
			delimiter: '/',
		}
		const mb2 = {
			id: btoa('INBOX/FLAGGED'),
			delimiter: '/',
		}
		const mb3 = {
			id: btoa('Archive'),
			delimiter: '/',
		}
		const mailboxes = [mb1, mb2, mb3]

		const hierarchy = buildMailboxHierarchy(mailboxes)

		expect(hierarchy).to.deep.equal([
			{
				id: btoa('INBOX'),
				delimiter: '/',
				folders: [],
			},
			{
				id: btoa('INBOX/FLAGGED'),
				delimiter: '/',
				folders: [],
			},
			{
				id: btoa('Archive'),
				delimiter: '/',
				folders: [],
			},
		])
	})

	it('builds a nested hierarchy with a prefix', () => {
		const mb1 = {
			id: btoa('INBOX.Archive'),
			delimiter: '.',
		}
		const mb2 = {
			id: btoa('INBOX.Archive.Sent'),
			delimiter: '.',
		}
		const mailboxes = [mb1, mb2]

		const hierarchy = buildMailboxHierarchy(mailboxes, true)

		expect(hierarchy).to.deep.equal([
			{
				id: btoa('INBOX.Archive'),
				delimiter: '.',
				folders: [
					{
						id: btoa('INBOX.Archive.Sent'),
						delimiter: '.',
						folders: [],
					},
				],
			},
		])
	})
})
