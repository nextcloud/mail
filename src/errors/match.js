/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {Error} error error
 * @param {object} matches matches
 */
export const matchError = async (error, matches) => {
	if (error.name in matches) {
		return await Promise.resolve(matches[error.name](error))
	}
	if ('default' in matches) {
		return await Promise.resolve(matches.default(error))
	}
	throw new Error('unhandled error in match: ' + error.name)
}
