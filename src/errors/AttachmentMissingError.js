/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export default class AttachmentMissingError extends Error {

	constructor(message) {
		super(message)
		this.name = AttachmentMissingError.getName()
		this.message = message
	}

	static getName() {
		return 'AttachmentMissingError'
	}

}
