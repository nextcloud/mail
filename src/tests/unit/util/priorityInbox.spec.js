/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	getPrioritySearchQueries,
	priorityImportantQuery,
	priorityOtherQuery,
} from '../../../util/priorityInbox.js'

describe('priorityInbox', () => {
	it('has correct query constants', () => {
		expect(priorityImportantQuery).toEqual('is:pi-important')
		expect(priorityOtherQuery).toEqual('is:pi-other')
	})

	it('returns all queries', () => {
		expect(getPrioritySearchQueries()).toEqual([
			'is:pi-important',
			'is:pi-other',
		])
	})
})
