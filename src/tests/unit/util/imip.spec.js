/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getParserManager } from '@nextcloud/calendar-js'
import { getUpdatedVEvent } from '../../../util/imip.js'

const vevent = (lines) => ['BEGIN:VEVENT', 'UID:abc', ...lines, 'END:VEVENT'].join('\r\n')
function master(stamp, lastModified) {
	return vevent([
		`DTSTAMP:${stamp}`,
		...(lastModified ? [`LAST-MODIFIED:${lastModified}`] : []),
		'DTSTART:20261113T130000Z',
		'DTEND:20261113T131500Z',
		'SUMMARY:Series',
		'RRULE:FREQ=DAILY;COUNT=2',
	])
}
function override(stamp, lastModified) {
	return vevent([
		`DTSTAMP:${stamp}`,
		...(lastModified ? [`LAST-MODIFIED:${lastModified}`] : []),
		'DTSTART:20261114T131500Z',
		'DTEND:20261114T133000Z',
		'SUMMARY:Series',
		'RECURRENCE-ID:20261114T130000Z',
	])
}

function parse(...vevents) {
	const ics = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//test//EN', 'METHOD:REQUEST', ...vevents, 'END:VCALENDAR'].join('\r\n')
	const parser = getParserManager().getParserForFileType('text/calendar')
	parser.parse(ics)
	return parser.getItemIterator().next().value
}

describe('imip util', () => {
	describe('getUpdatedVEvent', () => {
		it('returns undefined without a calendar', () => {
			expect(getUpdatedVEvent(undefined)).toBeUndefined()
		})

		it('returns the only event', () => {
			const calendar = parse(master('20261113T124459Z'))

			const result = getUpdatedVEvent(calendar)

			expect(result.isRecurring()).toBe(true)
		})

		it('returns the override if it was edited last', () => {
			const calendar = parse(master('20261113T124459Z'), override('20261113T124503Z'))

			const result = getUpdatedVEvent(calendar)

			expect(result.getFirstPropertyFirstValue('RECURRENCE-ID')).not.toBeNull()
		})

		it('returns the master if it was edited last', () => {
			const calendar = parse(master('20261113T125105Z'), override('20261113T124503Z'))

			const result = getUpdatedVEvent(calendar)

			expect(result.getFirstPropertyFirstValue('RECURRENCE-ID')).toBeNull()
		})

		it('prefers LAST-MODIFIED over DTSTAMP', () => {
			const calendar = parse(
				master('20261113T125105Z', '20261113T120000Z'),
				override('20261113T124503Z', '20261113T123000Z'),
			)

			const result = getUpdatedVEvent(calendar)

			expect(result.getFirstPropertyFirstValue('RECURRENCE-ID')).not.toBeNull()
		})

		it('falls back to DTSTAMP without LAST-MODIFIED', () => {
			const calendar = parse(master('20261113T125105Z'), override('20261113T124503Z'))

			const result = getUpdatedVEvent(calendar)

			expect(result.getFirstPropertyFirstValue('RECURRENCE-ID')).toBeNull()
		})

		it('returns the first event on ties', () => {
			const calendar = parse(master('20261113T125105Z'), override('20261113T125105Z'))

			const result = getUpdatedVEvent(calendar)

			expect(result.getFirstPropertyFirstValue('RECURRENCE-ID')).toBeNull()
		})
	})
})
