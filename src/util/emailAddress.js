/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import addressParser from 'address-rfc2822'

/**
 * Try to parse a string with address-rfc2822.
 * Returns an array of {label, email} objects, or an empty array on failure.
 *
 * @param {string} str The input string
 * @return {Array<{ label: string, email: string }>}
 */
function tryParse(str) {
	if (!str) {
		return []
	}

	try {
		return addressParser.parse(str).map(addr => ({
			label: addr.name() || addr.address,
			email: addr.address,
		}))
	} catch {
		return []
	}
}

/**
 * Split a string on commas that are not inside quotes or angle brackets.
 *
 * @param {string} str The input string
 * @return {string[]} The parts
 */
function splitOnCommas(str) {
	const parts = []
	let current = ''
	let inQuotes = false
	let inAngle = false

	for (let i = 0; i < str.length; i++) {
		const ch = str[i]

		if (ch === '"' && (i === 0 || str[i - 1] !== '\\')) {
			inQuotes = !inQuotes
		} else if (!inQuotes && ch === '<') {
			inAngle = true
		} else if (!inQuotes && ch === '>') {
			inAngle = false
		}

		if (ch === ',' && !inQuotes && !inAngle) {
			parts.push(current)
			current = ''
		} else {
			current += ch
		}
	}
	parts.push(current)
	return parts
}

/**
 * Extract a label and email address from a string like "John Doe <john@example.com>"
 * or just "john@example.com".
 *
 * @param {string} str The input string
 * @return {{ label: string, email: string } | null} Parsed result or null if no email found
 */
export function getLabelAndAddress(str) {
	if (!str) {
		return null
	}

	// Strip trailing delimiters that address-rfc2822 can't handle
	const cleaned = str.replace(/[,;]+$/, '').trim()
	const results = tryParse(cleaned)
	return results.length > 0 ? results[0] : null
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
	if (!str) {
		return []
	}

	// Normalize semicolons to commas (address-rfc2822 only supports commas)
	let normalized = str.replace(/;/g, ',')

	// Remove trailing commas
	normalized = normalized.replace(/,\s*$/, '')

	// First try: parse the whole string as-is (handles comma-separated lists)
	const results = tryParse(normalized)
	if (results.length > 0) {
		return results
	}

	// Second try: split by commas (respecting quotes/angle brackets),
	// then parse each part individually. This handles cases like
	// "not-an-email, alice@example.com" where the library rejects the whole string.
	const parts = splitOnCommas(normalized)
	const list = []
	for (const part of parts) {
		const trimmed = part.trim()
		if (!trimmed) continue

		const parsed = tryParse(trimmed)
		if (parsed.length > 0) {
			list.push(...parsed)
		} else if (trimmed.includes(' ') && trimmed.includes('@')) {
			// Try splitting on spaces for space-separated bare emails
			for (const word of trimmed.split(/\s+/)) {
				list.push(...tryParse(word))
			}
		}
	}
	return list
}
