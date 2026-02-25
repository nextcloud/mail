/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * RFC 5322 inspired regex for extracting an email address (with optional angle brackets)
 * from a string that may also contain a display name.
 *
 * Case-insensitive via the `i` flag.
 */
const emailRegex = /(<)?(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])(>)?/gi

/**
 * Extract a label and email address from a string like "John Doe <john@example.com>"
 * or just "john@example.com".
 *
 * @param {string} str The input string
 * @return {{ label: string, email: string } | null} Parsed result or null if no email found
 */
export function getLabelAndAddress(str) {
	// Reset lastIndex since we reuse a global regex
	emailRegex.lastIndex = 0
	const match = emailRegex.exec(str)

	if (!match) {
		return null
	}

	// Strip angle brackets from the matched email
	const email = match[0].replace(/[<>]/g, '').trim()
	// Everything before the matched portion is the display name
	const label = str.substring(0, match.index).trim() || email

	return { label, email }
}

/**
 * Parse a string containing one or more email addresses separated by
 * commas, semicolons, or spaces.
 *
 * Supports formats like:
 *   - "alice@example.com, bob@example.com"
 *   - "Alice <alice@example.com>; Bob <bob@example.com>"
 *   - "alice@example.com bob@example.com"
 *
 * @param {string} str The input string containing email addresses
 * @return {Array<{ label: string, email: string }>} List of parsed addresses
 */
export function parseEmailList(str) {
	let start = 0
	let inEmail = false
	const list = []

	for (let i = 0; i < str.length; i++) {
		const char = str[i]

		if (char === '@' || inEmail) {
			inEmail = true

			if ([';', ',', ' '].includes(char)) {
				const stringAddress = str.substring(start, i).trim()
				const labelAndAddress = getLabelAndAddress(stringAddress)

				if (labelAndAddress) {
					list.push(labelAndAddress)
				}

				inEmail = false
				start = i + 1
			}
		}
	}

	if (inEmail) {
		const stringAddress = str.substring(start).trim()
		const labelAndAddress = getLabelAndAddress(stringAddress)

		if (labelAndAddress) {
			list.push(labelAndAddress)
		}
	}

	return list
}
