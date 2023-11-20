/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * @typedef {{ enabled: bool, start: string, end: string, subject: string, message: string }} OutOfOfficeState
 */

/**
 * @typedef {{ state: OutOfOfficeState, script: string, untouchedScript: string }} OutOfOfficeStateResponse
 */

/**
 * @param {number} accountId
 * @return {Promise<OutOfOfficeStateResponse>}
 */
export async function fetch(accountId) {
	const url = generateUrl('/apps/mail/api/out-of-office/{accountId}', { accountId })

	const { data } = await axios.get(url)
	return data.data
}

/**
 * @param {number} accountId
 * @param {OutOfOfficeState} outOfOfficeState
 * @return {Promise<OutOfOfficeStateResponse>}
 */
export async function update(accountId, outOfOfficeState) {
	const url = generateUrl('/apps/mail/api/out-of-office/{accountId}', { accountId })

	const { data } = await axios.post(url, outOfOfficeState)
	return data.data
}

/**
 * @param {number} accountId
 * @return {Promise<OutOfOfficeStateResponse>}
 */
export async function followSystem(accountId) {
	const url = generateUrl('/apps/mail/api/out-of-office/{accountId}/follow-system', { accountId })

	const { data } = await axios.post(url)
	return data.data
}
