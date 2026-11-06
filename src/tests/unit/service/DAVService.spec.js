/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { getClient } from '../../../dav/client.js'
import { calendarContainsObject, getCalendarComponents, getCalendarObjects, getUserCalendars } from '../../../service/DAVService.js'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'user' }),
}))
vi.mock('@nextcloud/router', () => ({
	generateRemoteUrl: (path) => `https://cloud.example.com/remote.php/${path}`,
}))
vi.mock('../../../dav/client.js')
vi.mock('@nextcloud/axios')

const multiObjectIcs = [
	'BEGIN:VCALENDAR',
	'VERSION:2.0',
	'PRODID:-//Test//Test//EN',
	'METHOD:REQUEST',
	'BEGIN:VEVENT',
	'UID:event-1',
	'DTSTAMP:20261102T120000Z',
	'DTSTART:20261201T183000Z',
	'END:VEVENT',
	'BEGIN:VEVENT',
	'UID:event-1',
	'RECURRENCE-ID:20261208T183000Z',
	'DTSTAMP:20261102T120000Z',
	'DTSTART:20261208T190000Z',
	'END:VEVENT',
	'BEGIN:VTODO',
	'UID:todo-1',
	'DTSTAMP:20261102T120000Z',
	'END:VTODO',
	'END:VCALENDAR',
].join('\r\n')

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

	it('lists each calendar object of a file once', () => {
		expect(getCalendarObjects(multiObjectIcs)).toEqual([
			{ component: 'VEVENT', uid: 'event-1' },
			{ component: 'VTODO', uid: 'todo-1' },
		])
	})

	it('finds an existing calendar object by its UID', async () => {
		Axios.request.mockResolvedValueOnce({
			data: '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:response><d:href>/remote.php/dav/calendars/user/personal/abc.ics</d:href></d:response></d:multistatus>',
		})

		const exists = await calendarContainsObject('/remote.php/dav/calendars/user/personal/', { component: 'VEVENT', uid: 'a<b>&"c' })

		expect(exists).toBe(true)
		const request = Axios.request.mock.calls[0][0]
		expect(request.method).toBe('REPORT')
		expect(request.url).toBe('/remote.php/dav/calendars/user/personal/')
		expect(request.data).toContain('<c:comp-filter name="VEVENT">')
		expect(request.data).toContain('a&lt;b&gt;&amp;&quot;c')
	})

	it('reports a missing calendar object', async () => {
		Axios.request.mockResolvedValueOnce({
			data: '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"/>',
		})

		const exists = await calendarContainsObject('/remote.php/dav/calendars/user/personal/', { component: 'VEVENT', uid: 'event-1' })

		expect(exists).toBe(false)
	})
})
