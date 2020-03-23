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

const getParentId = (mailbox, hasPrefix) => {
	const top = hasPrefix ? 1 : 0
	const hierarchy = atob(mailbox.id).split(mailbox.delimiter)
	if (hierarchy.length <= top + 1 || atob(mailbox.id) === 'INBOX/FLAGGED') {
		return
	}
	if (hasPrefix) {
		return hierarchy[0] + mailbox.delimiter + hierarchy[1]
	} else {
		return hierarchy[0]
	}
}

export const buildMailboxHierarchy = (mailboxes, havePrefix) => {
	if (!mailboxes.length) {
		// Nothing to do
		return mailboxes
	}

	const cloned = mailboxes.map((mailbox) => {
		return {
			folders: [],
			...mailbox,
		}
	})
	const top = cloned.filter((mailbox) => getParentId(mailbox, havePrefix) === undefined)

	cloned.forEach((mailbox) => {
		if (top.indexOf(mailbox) !== -1) {
			return
		}

		const parentId = getParentId(mailbox, havePrefix)
		const parent = cloned.filter((mailbox) => atob(mailbox.id) === parentId)[0]
		if (parent) {
			parent.folders.push(mailbox)
		}
	})

	return top
}
