/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** Search query for important and unread messages inside priority inbox */
export const priorityImportantQuery = 'is:pi-important'

/** Search query for other messages inside priority inbox */
export const priorityOtherQuery = 'is:pi-other'

/**
 * Return an array of all search queries inside the priority inbox
 *
 * @return {(string)[]}
 */
export function getPrioritySearchQueries() {
	return [
		priorityImportantQuery,
		priorityOtherQuery,
	]
}
