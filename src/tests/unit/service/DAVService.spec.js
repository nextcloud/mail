/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient } from '../../../dav/client.js'
import { getCalendarComponents, getUserCalendars } from '../../../service/DAVService.js'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'user' }),
}))
vi.mock('@nextcloud/router', () => ({
	generateRemoteUrl: (path) => `https://cloud.example.com/remote.php/${path}`,
}))
vi.mock('../../../dav/client.js')

const writable = {
	acl: {
		ace: [{ grant: { privilege: { write: '' } } }],
	},
}

function mockCalendars(calendars) {
	getClient.mockReturnValue({
		getDirectoryContents: vi.fn().mockResolvedValue({ data: calendars }),
	})
}

describe('service/DAVService test suite', () => {
	beforeEach(() => {
		mockCalendars([
			{
				filename: '/personal',
				props: {
					...writable,
					displayname: 'Personal',
					'supported-calendar-component-set': { comp: { '@name': 'VEVENT' } },
				},
			},
			{
				filename: '/tasks',
				props: {
					...writable,
					displayname: 'Tasks',
					'supported-calendar-component-set': { comp: { '@name': 'VTODO' } },
				},
			},
			{
				filename: '/mixed',
				props: {
					...writable,
					displayname: 'Mixed',
					'supported-calendar-component-set': {
						comp: [{ '@name': 'VEVENT' }, { '@name': 'VTODO' }],
					},
				},
			},
			{
				// RFC 4791: no supported-calendar-component-set means everything is supported
				filename: '/legacy',
				props: {
					...writable,
					displayname: 'Legacy',
				},
			},
			{
				filename: '/shared-readonly',
				props: {
					displayname: 'Read-only',
					'supported-calendar-component-set': { comp: { '@name': 'VEVENT' } },
				},
			},
		])
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('only returns writable event calendars by default', async () => {
		const calendars = await getUserCalendars()

		expect(calendars.map((c) => c.displayname)).toEqual(['Personal', 'Mixed', 'Legacy'])
	})

	it('only returns calendars that accept tasks', async () => {
		const calendars = await getUserCalendars(['VTODO'])

		expect(calendars.map((c) => c.displayname)).toEqual(['Tasks', 'Mixed', 'Legacy'])
	})

	it('only returns calendars that accept all requested components', async () => {
		const calendars = await getUserCalendars(['VEVENT', 'VTODO'])

		expect(calendars.map((c) => c.displayname)).toEqual(['Mixed', 'Legacy'])
	})

	it('detects the components of an iCalendar file', () => {
		const ics = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Test//Test//EN',
			'METHOD:REQUEST',
			'BEGIN:VEVENT',
			'UID:event-1',
			'DTSTAMP:20261102T120000Z',
			'DTSTART:20261201T183000Z',
			'END:VEVENT',
			'BEGIN:VTODO',
			'UID:todo-1',
			'DTSTAMP:20261102T120000Z',
			'END:VTODO',
			'END:VCALENDAR',
		].join('\r\n')

		expect(getCalendarComponents(ics)).toEqual(['VEVENT', 'VTODO'])
	})
})
