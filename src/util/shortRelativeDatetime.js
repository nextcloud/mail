/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import curry from 'lodash/fp/curry'
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

export const shortRelativeDatetime = shortDatetime(new Date())
