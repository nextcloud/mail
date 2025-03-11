/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Maps a dav collection to our calendar object model
 *
 * @param {object} calendar The calendar object from the cdav library
 * @param {object} currentUserPrincipal The principal model of the current user principal
 * @return {object}
 */
export default function toCalendar(calendar, currentUserPrincipal) {
	const owner = calendar.owner
	let isSharedWithMe = false
	if (!currentUserPrincipal) {
		// If the user is not authenticated, the calendar
		// will always be marked as shared with them
		isSharedWithMe = true
	} else {
		isSharedWithMe = (owner !== currentUserPrincipal.url)
	}
	const displayname = calendar.displayname || getCalendarUriFromUrl(calendar.url)

	const color = calendar.color

	const shares = []
	if (!!currentUserPrincipal && Array.isArray(calendar.shares)) {
		for (const share of calendar.shares) {
			if (share.href === currentUserPrincipal.principalScheme) {
				continue
			}

			shares.push(mapDavShareeToSharee(share))
		}
	}

	const order = +calendar.order || 0

	return {
		// get last part of url
		id: calendar.url.split('/').slice(-2, -1)[0],
		displayname,
		color,
		order,
		enabled: calendar.enabled !== false,
		owner,
		readOnly: !calendar.isWriteable(),
		tasks: {},
		url: calendar.url,
		dav: calendar,
		shares,
		supportsEvents: calendar.components.includes('VEVENT'),
		supportsTasks: calendar.components.includes('VTODO'),
		loadedCompleted: false,
		isSharedWithMe,
		canBeShared: calendar.isShareable(),
	}
}

/**
 * Gets the calendar uri from the url
 *
 * @param {string} url The url to get calendar uri from
 * @return {string}
 */
function getCalendarUriFromUrl(url) {
	if (url.endsWith('/')) {
		url = url.substring(0, url.length - 1)
	}

	return url.substring(url.lastIndexOf('/') + 1)
}

/*
* Maps a dav collection to the sharee array
*
* @param {object} sharee The sharee object from the cdav library shares
* @return {object}
*/
export function mapDavShareeToSharee(sharee) {
	const id = sharee.href.split('/').slice(-1)[0]
	let name = sharee['common-name']
		? sharee['common-name']
		: sharee.href

	if (sharee.href.startsWith('principal:principals/groups/') && name === sharee.href) {
		name = sharee.href.slice(28)
	}

	return {
		displayName: name,
		id,
		writeable: sharee.access[0].endsWith('read-write'),
		isGroup: sharee.href.startsWith('principal:principals/groups/'),
		isCircle: sharee.href.startsWith('principal:principals/circles/'),
		uri: sharee.href,
	}
}
