/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const isErrorResponse = (resp) => {
	return 'x-mail-response' in resp.headers && resp.data.status === 'error'
}

export const parseErrorResponse = (resp) => {
	if (!isErrorResponse(resp)) {
		return resp
	}

	const { debug, type, code, message, trace } = resp.data.data || {}

	return {
		isError: true,
		debug: !!debug,
		type,
		code,
		message,
		trace,
	}
}
