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

import clone from 'lodash/fp/clone.js'

const specialRolesOrder = ['all', 'inbox', 'flagged', 'drafts', 'sent', 'archive', 'junk', 'trash']

export const sortMailboxes = (mailboxes, account) => {
	const c = clone(mailboxes)
	c.sort((f1, f2) => {
		if (f1.specialUse.length && f2.specialUse.length) {
			const s1 = specialRolesOrder.indexOf(f1.specialUse[0])
			const s2 = specialRolesOrder.indexOf(f2.specialUse[0])

			if (s1 === s2) {
				return f1.name.localeCompare(f2.name)
			}

			return s1 - s2
		} else if (f1.specialUse.length) {
			return -1
		} else if (f2.specialUse.length) {
			return 1
		} else if (f1.databaseId === account.snoozeMailboxId) {
			// Sort Snoozed mailbox to specialRole mailboxes.
			// Because this mailbox does not have specialUse,
			// we need to check the databaseId for snoozeMailboxId
			return -1
		} else {
			return f1.name.localeCompare(f2.name)
		}
	})
	return c
}
