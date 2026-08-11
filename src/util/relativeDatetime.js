/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import curry from 'lodash/fp/curry.js'

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate())

function startOfPreviousDay(date) {
	const start = startOfDay(date)
	start.setDate(start.getDate() - 1)
	return start
}

const isToday = (ref, date) => date >= startOfDay(ref)
const isYesterday = (ref, date) => date >= startOfPreviousDay(ref) && date < startOfDay(ref)

// For the grouped list: a "Today"/"Yesterday" section header already shows the day,
// so today and yesterday need only the time.
export const groupedDatetime = curry((ref, date) => {
	const momentDate = moment(date)
	if (isToday(ref, date) || isYesterday(ref, date)) {
		return momentDate.format('LT')
	}
	// Within the previous week?
	if (date.getTime() > (ref.getTime() - 30 * 60 * 24 * 7 * 1000)) {
		return momentDate.format('dd')
	}
	// Within the previous year?
	if (date.getTime() > (ref.getTime() - 30 * 60 * 24 * 365 * 1000)) {
		return momentDate.format('MMM D')
	}
	// Older
	return momentDate.format('MMM D, YYYY')
})

// For the flat (ungrouped) list: no section header, so yesterday must label itself.
export const flatDatetime = curry((ref, date) => {
	if (isYesterday(ref, date)) {
		return t('mail', 'Yesterday {time}', { time: moment(date).format('LT') })
	}
	return groupedDatetime(ref, date)
})

export const detailedDatetime = curry((ref, date) => {
	// Older than yesterday?
	if (date < startOfPreviousDay(ref)) {
		return moment(date).format('LLL')
	}
	return flatDatetime(ref, date)
})

export function messageDateTime(date) {
	return moment(date * 1000).format('lll')
}

export const groupedRelativeDatetime = (date) => groupedDatetime(new Date(), date)
export const flatRelativeDatetime = (date) => flatDatetime(new Date(), date)
export const detailedRelativeDatetime = (date) => detailedDatetime(new Date(), date)
