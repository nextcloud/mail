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

import _ from 'lodash'

const specialRolesOrder = ['all', 'inbox', 'flagged', 'drafts', 'sent', 'archive', 'junk', 'trash']

export const sortMailboxes = mailboxes => {
	const clone = _.clone(mailboxes)
	clone.sort((f1, f2) => {
		if (f1.specialUse.length && f2.specialUse.length) {
			const s1 = specialRolesOrder.indexOf(f1.specialUse[0])
			const s2 = specialRolesOrder.indexOf(f2.specialUse[0])

			if (s1 === s2) {
				return atob(f1.id).localeCompare(atob(f2.id))
			}

			return s1 - s2
		} else if (f1.specialUse.length) {
			return -1
		} else if (f2.specialUse.length) {
			return 1
		} else {
			return atob(f1.id).localeCompare(atob(f2.id))
		}
	})
	return clone
}
