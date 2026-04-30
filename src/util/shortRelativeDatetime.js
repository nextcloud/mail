/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import moment from '@nextcloud/moment'
import curry from 'lodash/fp/curry.js'
import { translate as t } from '@nextcloud/l10n'

export const shortDatetime = curry((ref, date, withLabel = false) => {
	const momentDate = moment(date)
	const startOfToday = new Date(ref.getFullYear(), ref.getMonth(), ref.getDate())
	const startOfYesterday = new Date(startOfToday)
	startOfYesterday.setDate(startOfYesterday.getDate() - 1)
	// Within the same day?
	if (date >= startOfToday) {
		return momentDate.format('H:mm')
	}
	// Yesterday?
	if (date >= startOfYesterday) {
		return withLabel
			? t('mail', 'Yesterday') + ' ' + momentDate.format('H:mm')
			: momentDate.format('H:mm')
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

export function messageDateTime(date) {
	return moment(date * 1000).format('lll')
}

export const shortRelativeDatetime = (date) => shortDatetime(new Date(), date, false)
export const longRelativeDatetime = (date) => shortDatetime(new Date(), date, true)
