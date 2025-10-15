/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class MailboxNotCachedError extends Error {

	constructor(message) {
		super(message)
		this.name = MailboxNotCachedError.getName()
		this.message = message
	}

	static getName() {
		return 'MailboxNotCachedError'
	}

}
