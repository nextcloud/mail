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
		return addressParser.parse(str).map((addr) => ({
			label: addr.name() || addr.address,
			email: addr.address,
		}))
	} catch {
		return []
	}
}

/**
 * Split a string on delimiter characters (commas and semicolons) that are
 * not inside quotes or angle brackets.
 *
 * @param {string} str The input string
 * @return {string[]} The parts
 */
function splitOnDelimiters(str) {
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

		if ((ch === ',' || ch === ';') && !inQuotes && !inAngle) {
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
 * @param {string|null|undefined} str The input string
 * @return {{ label: string, email: string } | null} Parsed result or null if no email found
 */
export function getLabelAndAddress(str) {
	if (!str) {
		return null
	}

	// Trim first so trailing delimiters followed by whitespace are still removed
	const cleaned = str.trim().replace(/[,;]+$/, '').trim()
	const results = tryParse(cleaned)
	return results.length > 0 ? results[0] : null
}

/**
 * Parse a string containing one or more email addresses separated by
 * commas or semicolons, with limited support for spaces between bare
 * email addresses.
 *
 * Supports formats like:
 *   - "alice@example.com, bob@example.com"
 *   - "Alice <alice@example.com>; Bob <bob@example.com>"
 *   - "alice@example.com bob@example.com" (bare email addresses only)
 *
 * @param {string|null|undefined} str The input string containing email addresses
 * @return {Array<{ label: string, email: string }>} List of parsed addresses
 */
export function parseEmailList(str) {
	if (!str) {
		return []
	}

	// Split on commas and semicolons (respecting quotes/angle brackets),
	// then normalize to a comma-separated string for address-rfc2822.
	const parts = splitOnDelimiters(str)
	const normalized = parts.map((p) => p.trim()).filter(Boolean).join(', ')

	// First try: parse the whole normalized string (handles clean lists)
	const results = tryParse(normalized)
	if (results.length > 0) {
		return results
	}

	// Second try: parse each part individually. This handles cases like
	// "not-an-email, alice@example.com" where the library rejects the whole string.
	const list = []
	for (const part of parts) {
		const trimmed = part.trim()
		if (!trimmed) {
			continue
		}

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

/**
 * Check if a target email address can be added as an alias to the given account.
 * Quick alias addition is strictly allowed only for domains explicitly configured
 * in account.aliasDomains (or their subdomains).
 *
 * @param {string} targetEmail The target email address
 * @param {object} account The active mail account (with optional aliasDomains)
 * @return {boolean} True if the target email belongs to a compatible domain/subdomain
 */
export function isCompatibleAliasDomain(targetEmail, account) {
	if (!targetEmail || !account) {
		return false
	}

	const cleanTarget = targetEmail.trim().toLowerCase()
	const targetAt = cleanTarget.lastIndexOf('@')
	if (targetAt === -1) {
		return false
	}
	const targetDomain = cleanTarget.slice(targetAt + 1)
	if (!targetDomain) {
		return false
	}

	// Parse explicitly configured alias domains (string or array)
	let rawDomains = account.aliasDomains
	if (typeof rawDomains === 'string') {
		rawDomains = rawDomains.split(/[,;\s]+/).map((d) => d.trim().toLowerCase()).filter(Boolean)
	}

	const allowedDomains = new Set()
	if (Array.isArray(rawDomains)) {
		rawDomains.forEach((dom) => {
			let cleanDom = dom.trim().toLowerCase()
			const atIdx = cleanDom.lastIndexOf('@')
			if (atIdx !== -1) {
				cleanDom = cleanDom.slice(atIdx + 1)
			}
			cleanDom = cleanDom.replace(/^\.+|\.+$/g, '').trim()
			if (cleanDom) {
				allowedDomains.add(cleanDom)
			}
		})
	}

	// If no alias domains are explicitly configured, do not offer quick alias creation
	if (allowedDomains.size === 0) {
		return false
	}

	for (const accDomain of allowedDomains) {
		// Exact match
		if (targetDomain === accDomain) {
			return true
		}
		// Target is a subdomain of allowed domain (e.g. nek-adhs-pkh@sarondra.gryzia.de for sarondra.gryzia.de or gryzia.de)
		if (targetDomain.endsWith('.' + accDomain)) {
			return true
		}
	}

	return false
}

/**
 * Sort a list of alias objects or email addresses:
 * First by domain/host (case-insensitive), then alphabetically by local part / username.
 *
 * @template T
 * @param {T[]} aliases List of alias objects (with .alias or .emailAddress) or raw email strings
 * @return {T[]} Sorted array
 */
export function sortAliases(aliases) {
	if (!Array.isArray(aliases)) {
		return []
	}

	return [...aliases].sort((a, b) => {
		const emailA = typeof a === 'string' ? a : (a?.alias || a?.emailAddress || '')
		const emailB = typeof b === 'string' ? b : (b?.alias || b?.emailAddress || '')

		const atIndexA = emailA.lastIndexOf('@')
		const atIndexB = emailB.lastIndexOf('@')

		const localA = (atIndexA !== -1 ? emailA.slice(0, atIndexA) : emailA).toLowerCase()
		const domainA = (atIndexA !== -1 ? emailA.slice(atIndexA + 1) : '').toLowerCase()

		const localB = (atIndexB !== -1 ? emailB.slice(0, atIndexB) : emailB).toLowerCase()
		const domainB = (atIndexB !== -1 ? emailB.slice(atIndexB + 1) : '').toLowerCase()

		const domainCompare = domainA.localeCompare(domainB)
		if (domainCompare !== 0) {
			return domainCompare
		}

		return localA.localeCompare(localB)
	})
}
