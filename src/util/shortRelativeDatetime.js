/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import curry from 'lodash/fp/curry.js'
import moment from '@nextcloud/moment'

export const shortDatetime = curry((ref, date) => {
	const momentDate = moment(date)
	// Within the same day?
	if (ref.getFullYear() === date.getFullYear()
		&& ref.getMonth() === date.getMonth()
		&& ref.getDate() === date.getDate()) {
		return momentDate.format('H:mm')
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

export const messageDateTime = (date) => {
	return moment(date * 1000).format('lll')
}

export const shortRelativeDatetime = shortDatetime(new Date())
