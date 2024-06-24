/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default class ManyRecipientsError extends Error {

	constructor(message) {
		super(message)
		this.name = ManyRecipientsError.getName()
		this.message = message
	}

	static getName() {
		return 'ManyRecipientsError'
	}

}
