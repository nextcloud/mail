/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import clone from 'lodash/fp/clone.js'

const specialRolesOrder = ['inbox', 'flagged', 'drafts', 'sent', 'archive', 'all', 'junk', 'trash']

export function sortMailboxes(mailboxes, account) {
	const c = clone(mailboxes)

	// The snoozed mailbox goes right after the special-use mailboxes, unless it is inside
	// an ordinary mailbox: then it has to come after that parent, because a child is only
	// attached to a parent that is already in the list (addMailboxToState)
	const snoozed = c.find((mb) => mb.databaseId === account.snoozeMailboxId)
	const snoozedParentName = snoozed?.name.substring(0, snoozed.name.lastIndexOf(snoozed.delimiter))
	const snoozedFirst = snoozed !== undefined
		&& !c.some((mb) => mb !== snoozed && mb.name === snoozedParentName && !mb.specialUse.length)

	c.sort((f1, f2) => {
		if (f1.specialUse.length && f2.specialUse.length) {
			const s1 = specialRolesOrder.indexOf(f1.specialUse[0])
			const s2 = specialRolesOrder.indexOf(f2.specialUse[0])

			if (s1 === s2) {
				return f1.name.localeCompare(f2.name)
			}

			// Handle invalid special uses
			// if both are invalid it's caught at the previous if
			if (s1 === -1) {
				return 1
			} else if (s2 === -1) {
				return -1
			}

			return s1 - s2
		} else if (f1.specialUse.length) {
			return -1
		} else if (f2.specialUse.length) {
			return 1
		} else if (snoozedFirst && f1 === snoozed) {
			// Sort Snoozed mailbox to specialRole mailboxes.
			// Because this mailbox does not have specialUse,
			// we need to check the databaseId for snoozeMailboxId
			return -1
		} else if (snoozedFirst && f2 === snoozed) {
			// The same rule from the other side, or the comparator is inconsistent
			// and the result depends on the order the mailboxes arrive in
			return 1
		} else {
			return f1.name.localeCompare(f2.name)
		}
	})
	return c
}
