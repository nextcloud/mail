/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { groupEnvelopesByDate } from '../../../util/groupedEnvelopes.js'

describe('groupEnvelopesByDate', () => {
	const now = new Date('2025-10-07T12:00:00Z').getTime()

	const makeEnvelope = (date) => ({ dateInt: Math.floor(date.getTime() / 1000) })

	it('groups envelopes into lastHour, yesterday, lastMonth, July, and 2024', () => {
		const envelopes = [
			makeEnvelope(new Date('2025-10-07T11:30:00Z')),
			makeEnvelope(new Date('2025-10-06T18:00:00Z')),
			makeEnvelope(new Date('2025-09-10T12:00:00Z')),
			makeEnvelope(new Date('2025-07-01T12:00:00Z')),
			makeEnvelope(new Date('2024-12-25T12:00:00Z')),
		]

		const result = groupEnvelopesByDate(envelopes, now, 'newest')
		const sections = Object.keys(result)

		expect(sections).toEqual(
			expect.arrayContaining([
				'lastHour',
				'yesterday',
				'lastMonth',
				'July',
				'2024',
			]),
		)

		expect(result.lastHour).toHaveLength(1)
		expect(result.yesterday).toHaveLength(1)
		expect(result.lastMonth).toHaveLength(1)
		expect(result.July).toHaveLength(1)
		expect(result['2024']).toHaveLength(1)
	})

	it('respects sortOrder = oldest', () => {
		const newer = makeEnvelope(new Date('2025-10-07T11:50:00Z'))
		const older = makeEnvelope(new Date('2025-10-07T11:10:00Z'))

		const result = groupEnvelopesByDate([newer, older], now, 'oldest')
		const lastHourGroup = result.lastHour

		expect(lastHourGroup[0]).toEqual(older)
	})
})
