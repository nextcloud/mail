/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export function sortEnvelopes(envelopes, sortOrder = 'newest') {
	if (sortOrder === 'oldest') {
		return [...envelopes].sort((a, b) => {
			return a.dateInt < b.dateInt ? -1 : 1
		})
	}
	return [...envelopes]
}
