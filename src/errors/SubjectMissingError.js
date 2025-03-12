/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export default class SubjectMissingError extends Error {

	constructor(message) {
		super(message)
		this.name = SubjectMissingError.getName()
		this.message = message

	}

	static getName() {
		return 'SubjectMissingError'
	}

}
