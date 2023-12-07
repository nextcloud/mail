/**
	* @copyright Copyright (c) 2022 Richard Steinmetz
	*
	* @author Richard Steinmetz <richard@steinmetz.cloud>
	*
	* @license AGPL-3.0-or-later
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
	* along with this program. If not, see <http://www.gnu.org/licenses/>.
	*
	*/

import DavClient from '@nextcloud/cdav-library'
import { generateRemoteUrl } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'

let client = null
const getClient = () => {
	if (client) {
		return client
	}

	client = new DavClient({
		rootUrl: generateRemoteUrl('dav'),
	}, () => {
		const headers = {
			'X-Requested-With': 'XMLHttpRequest',
			requesttoken: getRequestToken(),
			'X-NC-CalDAV-Webcal-Caching': 'On',
		}
		const xhr = new XMLHttpRequest()
		const oldOpen = xhr.open

		// override open() method to add headers
		xhr.open = function() {
			const result = oldOpen.apply(this, arguments)
			for (const name in headers) {
				xhr.setRequestHeader(name, headers[name])
			}

			return result
		}

		OC.registerXHRForErrorProcessing(xhr) // eslint-disable-line no-undef
		return xhr
	})

	return getClient()
}

/**
 * Initializes the client for use in the user-view
 */
export async function initializeClientForUserView() {
	await getClient().connect({ enableCalDAV: true })
}

/**
 * Returns the Current User Principal
 *
 * @return {Principal}
 */
export function getCurrentUserPrincipal() {
	return getClient().currentUserPrincipal
}

/**
 * Fetch all calendars from the server
 *
 * @return {Promise<CalendarHome>}
 */
export function getCalendarHome() {
	return getClient().calendarHomes[0]
}

/**
 * Fetch all collections in the calendar home from the server
 *
 * @return {Promise<Collection[]>}
 */
export async function findAll() {
	return await getCalendarHome().findAllCalDAVCollectionsGrouped()
}
