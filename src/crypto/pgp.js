/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {Text} message the message
 * @return {boolean|*}
 */
export const isPgpgMessage = (message) =>
	message.format === 'plain' && message.value.startsWith('-----BEGIN PGP MESSAGE-----')

export function isPgpText(text) {
	return text.startsWith('-----BEGIN PGP MESSAGE-----')
}
