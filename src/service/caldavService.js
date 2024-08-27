/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	await getClient().connect({
		enableCalDAV: true,
		enableCardDAV: true,
	})
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
 * Fetch all address books from the server
 *
 * @return {Promise<AddressBookHome>}
 */
export function getAddressBookHomes() {
	return getClient().addressBookHomes[0]
}

/**
 * Fetch all collections in the calendar home from the server
 *
 * @return {Promise<Collection[]>}
 */
export async function findAll() {
	return {
		calendarGroups: await getCalendarHome().findAllCalDAVCollectionsGrouped(),
		addressBooks: await getAddressBookHomes().findAllAddressBooks(),
	}
}
