/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {object} mailbox object
 * @param {string} rights list of rights to check
 * @return {boolean}
 */
export function mailboxHasRights(mailbox, rights) {
	if (!mailbox.myAcls) {
		return true
	}

	const acls = [...mailbox.myAcls]

	for (const right of [...rights]) {
		if (!acls.includes(right)) {
			return false
		}
	}

	return true
}
