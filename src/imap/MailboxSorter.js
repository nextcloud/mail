/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import clone from 'lodash/fp/clone.js'

const specialRolesOrder = ['inbox', 'flagged', 'drafts', 'sent', 'archive', 'all', 'junk', 'trash']

export const sortMailboxes = (mailboxes, account) => {
	const c = clone(mailboxes)
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
