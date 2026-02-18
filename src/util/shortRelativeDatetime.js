/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCanonicalLocale } from '@nextcloud/l10n'
import curry from 'lodash/fp/curry.js'

export const shortDatetime = curry((ref, date) => {
	const locale = getCanonicalLocale()
	// Within the same day?
	if (ref.getFullYear() === date.getFullYear()
		&& ref.getMonth() === date.getMonth()
		&& ref.getDate() === date.getDate()) {
		return new Intl.DateTimeFormat(locale, { hour: 'numeric', minute: '2-digit' }).format(date)
	}
	// Within the previous week?
	if (date.getTime() > (ref.getTime() - 30 * 60 * 24 * 7 * 1000)) {
		return new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date)
	}
	// Within the previous year?
	if (date.getTime() > (ref.getTime() - 30 * 60 * 24 * 365 * 1000)) {
		return new Intl.DateTimeFormat(locale, { month: 'short', day: 'numeric' }).format(date)
	}
	// Older
	return new Intl.DateTimeFormat(locale, { month: 'short', day: 'numeric', year: 'numeric' }).format(date)
})

export const shortRelativeDatetime = shortDatetime(new Date())
