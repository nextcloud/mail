/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
export default class CouldNotConnectError extends Error {

	constructor(message) {
		super(message)
		this.name = CouldNotConnectError.getName()
	}

	static getName() {
		return 'CouldNotConnectError'
	}

}
