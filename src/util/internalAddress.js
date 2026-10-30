/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Split the text entered for a new internal address into the address and its type.
 * Spaces around the text and a leading "@" before a domain are removed.
 *
 * @param {string} input The entered text
 * @return {{ address: string, type: 'domain'|'individual' }}
 */
export function parseInternalAddress(input) {
	const address = input.trim()
	const parts = address.split('@')
	if (parts.length !== 2) {
		return { address, type: 'domain' }
	}
	if (parts[0].length === 0) {
		return { address: parts[1].trim(), type: 'domain' }
	}
	return { address, type: 'individual' }
}
