/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCanonicalLocale } from '@nextcloud/l10n'

/**
 * Format a date as a localized long date with time (equivalent to moment's LLL format).
 *
 * @param {Date} date The date to format
 * @return {string} Formatted date string (e.g. "September 4, 1986, 8:30 PM")
 */
export function formatDateTime(date) {
	return new Intl.DateTimeFormat(getCanonicalLocale(), {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
		hour: 'numeric',
		minute: 'numeric',
	}).format(date)
}

/**
 * Format a Unix timestamp as a localized long date with time.
 *
 * @param {number} timestamp Unix timestamp in seconds
 * @return {string} Formatted date string
 */
export function formatDateTimeFromUnix(timestamp) {
	return formatDateTime(new Date(timestamp * 1000))
}
