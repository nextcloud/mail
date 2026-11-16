/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {object} vEvent VEVENT component
 * @return {number} Time of the last edit in ms, 0 if unknown
 */
function getLastEditTime(vEvent) {
	const stamp = vEvent.getFirstPropertyFirstValue('LAST-MODIFIED')
		?? vEvent.getFirstPropertyFirstValue('DTSTAMP')
	return stamp?.jsDate?.getTime() ?? 0
}

/**
 * An iMIP message carries the whole calendar object, i.e. the series master and
 * all its overrides. The component edited last is the one the message is about.
 *
 * @param {object} vCalendar Calendar component of the iMIP attachment
 * @return {object|undefined} The VEVENT that was edited last, the first one on ties
 */
export function getUpdatedVEvent(vCalendar) {
	let updated
	let updatedTime = -1
	for (const vEvent of vCalendar?.getComponentIterator('VEVENT') ?? []) {
		const time = getLastEditTime(vEvent)
		if (time > updatedTime) {
			updated = vEvent
			updatedTime = time
		}
	}
	return updated
}
