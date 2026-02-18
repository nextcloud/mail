/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCanonicalLocale } from '@nextcloud/l10n'

function fmt(options) {
	return (date) => new Intl.DateTimeFormat(getCanonicalLocale(), options).format(date)
}

// moment 'LLL' → "December 25, 2023 at 2:30 PM"
export const formatLongDateTime = fmt({ dateStyle: 'long', timeStyle: 'short' })
// moment 'LL' → "December 25, 2023"
export const formatLongDate = fmt({ dateStyle: 'long' })
// moment 'L' → "12/25/23"
export const formatShortDate = fmt({ dateStyle: 'short' })
// moment 'LT' → "2:30 PM"
export const formatTime = fmt({ timeStyle: 'short' })
// moment 'll' → "Dec 25, 2023"
export const formatMediumDate = fmt({ dateStyle: 'medium' })
// moment 'lll' → "Dec 25, 2023, 2:30 PM"
export const formatMediumDateTime = fmt({ dateStyle: 'medium', timeStyle: 'short' })
// moment 'ddd LT' → "Mon 2:30 PM"
export const formatWeekdayTime = fmt({ weekday: 'short', hour: 'numeric', minute: 'numeric' })

/**
 * Format a Date as an ISO 8601 string with local timezone offset.
 * Replaces moment().format() which produces ISO strings with timezone.
 *
 * @param {Date} date The date to format
 * @return {string} ISO 8601 string with timezone offset
 */
export function toISOLocalString(date) {
	const off = date.getTimezoneOffset()
	const d = new Date(date.getTime() - off * 60000)
	const sign = off <= 0 ? '+' : '-'
	const hh = String(Math.floor(Math.abs(off) / 60)).padStart(2, '0')
	const mm = String(Math.abs(off) % 60).padStart(2, '0')
	return d.toISOString().slice(0, -1) + sign + hh + ':' + mm
}
