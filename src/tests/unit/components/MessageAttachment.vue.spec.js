/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showSuccess } from '@nextcloud/dialogs'
import { shallowMount } from '@vue/test-utils'
import MessageAttachment from '../../../components/MessageAttachment.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { downloadAttachment } from '../../../service/AttachmentService.js'
import { calendarContainsObject, importCalendarEvent } from '../../../service/DAVService.js'

vi.mock('@nextcloud/dialogs')
vi.mock('../../../service/AttachmentService.js')
vi.mock('../../../service/DAVService.js', async (importOriginal) => ({
	...(await importOriginal()),
	calendarContainsObject: vi.fn(),
	getUserCalendars: vi.fn().mockResolvedValue([]),
	importCalendarEvent: vi.fn(),
}))

const ics = [
	'BEGIN:VCALENDAR',
	'VERSION:2.0',
	'PRODID:-//Test//Test//EN',
	'BEGIN:VEVENT',
	'UID:event-1',
	'DTSTAMP:20261102T120000Z',
	'DTSTART:20261201T183000Z',
	'END:VEVENT',
	'END:VCALENDAR',
].join('\r\n')

const calendar = {
	displayname: 'Personal',
	url: '/remote.php/dav/calendars/user/personal/',
}

function mountAttachment() {
	return shallowMount(MessageAttachment, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			id: '1',
			fileName: 'invite.ics',
			url: '/attachment/1',
			size: 1928,
			mime: 'text/calendar',
			mimeUrl: '/mime/calendar.svg',
			isCalendarEvent: true,
		},
	})
}

describe('MessageAttachment', () => {
	beforeEach(() => {
		downloadAttachment.mockResolvedValue(ics)
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('imports the attachment into the chosen calendar', async () => {
		importCalendarEvent.mockResolvedValueOnce([])

		await mountAttachment().vm.importCalendar(calendar)()

		expect(importCalendarEvent).toHaveBeenCalledWith(calendar.url, ics)
		expect(showSuccess).toHaveBeenCalled()
		expect(calendarContainsObject).not.toHaveBeenCalled()
	})

	it('explains that the event is already in the calendar', async () => {
		importCalendarEvent.mockRejectedValueOnce(new Error('400 Bad Request'))
		calendarContainsObject.mockResolvedValueOnce(true)

		await mountAttachment().vm.importCalendar(calendar)()

		expect(calendarContainsObject).toHaveBeenCalledWith(calendar.url, { component: 'VEVENT', uid: 'event-1' })
		expect(showError).toHaveBeenCalledWith('This event is already in the calendar Personal')
	})

	it('falls back to the generic error for other failures', async () => {
		importCalendarEvent.mockRejectedValueOnce(new Error('500 Internal Server Error'))
		calendarContainsObject.mockResolvedValueOnce(false)

		await mountAttachment().vm.importCalendar(calendar)()

		expect(showError).toHaveBeenCalledWith('Could not create event')
	})

	it('falls back to the generic error if the check fails too', async () => {
		importCalendarEvent.mockRejectedValueOnce(new Error('500 Internal Server Error'))
		calendarContainsObject.mockRejectedValueOnce(new Error('network'))

		await mountAttachment().vm.importCalendar(calendar)()

		expect(showError).toHaveBeenCalledWith('Could not create event')
	})
})
