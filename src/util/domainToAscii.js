/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Browser replacement for `domainToASCII` of Node's `url` module.
 *
 * @param {string} domain The domain, possibly containing non-ASCII labels
 * @return {string} The punycode (A-label) domain, or an empty string if invalid
 */
export function domainToASCII(domain) {
	try {
		return new URL(`http://${domain}`).hostname
	} catch {
		return ''
	}
}
