/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { sortEnvelopes } from '../../../util/sortEnvelopes.js'

describe('sortEnvelopes', () => {
	const newer = { databaseId: 1, dateInt: 200 }
	const older = { databaseId: 2, dateInt: 100 }

	it('keeps the given order for newest first', () => {
		const envelopes = [newer, older]

		const result = sortEnvelopes(envelopes, 'newest')

		expect(result).toEqual([newer, older])
		expect(result).not.toBe(envelopes)
	})

	it('orders ascending by date for oldest first', () => {
		const envelopes = [newer, older]

		const result = sortEnvelopes(envelopes, 'oldest')

		expect(result).toEqual([older, newer])
		expect(envelopes).toEqual([newer, older])
	})
})
