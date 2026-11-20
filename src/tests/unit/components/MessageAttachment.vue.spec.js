/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showSuccess } from '@nextcloud/dialogs'
import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MessageAttachment from '../../../components/MessageAttachment.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { downloadAttachment, saveAttachmentToFiles } from '../../../service/AttachmentService.js'
import { importCalendarEvent } from '../../../service/DAVService.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('../../../service/AttachmentService.js', () => ({
	downloadAttachment: vi.fn(),
	saveAttachmentToFiles: vi.fn().mockResolvedValue(undefined),
}))
vi.mock('../../../service/DAVService.js', () => ({
	importCalendarEvent: vi.fn(),
}))
vi.mock('../../../util/filePicker.js', () => ({
	pickFolder: vi.fn().mockResolvedValue('/Documents'),
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

function httpError(status, data) {
	return Object.assign(new Error(`Request failed with status code ${status}`), {
		response: { status, data },
	})
}

describe('MessageAttachment', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
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
		expect(showError).not.toHaveBeenCalled()
	})

	it('explains that the event is already in the calendar', async () => {
		importCalendarEvent.mockRejectedValueOnce(httpError(409, `<?xml version="1.0" encoding="utf-8"?>
<d:error xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav">
  <cal:no-uid-conflict><d:href>/remote.php/dav/calendars/user/personal/event-1.ics</d:href></cal:no-uid-conflict>
</d:error>`))

		await mountAttachment().vm.importCalendar(calendar)()

		expect(showError).toHaveBeenCalledWith('This event is already in the calendar Personal')
	})

	it('falls back to the generic error for other conflicts', async () => {
		importCalendarEvent.mockRejectedValueOnce(httpError(409, '<d:error xmlns:d="DAV:"/>'))

		await mountAttachment().vm.importCalendar(calendar)()

		expect(showError).toHaveBeenCalledWith('Could not create event')
	})

	it('falls back to the generic error for older servers', async () => {
		importCalendarEvent.mockRejectedValueOnce(httpError(400, 'Calendar object with uid already exists in this calendar collection.'))

		await mountAttachment().vm.importCalendar(calendar)()

		expect(showError).toHaveBeenCalledWith('Could not create event')
	})

	it('falls back to the generic error if the attachment cannot be downloaded', async () => {
		downloadAttachment.mockRejectedValueOnce(new Error('Network Error'))

		await mountAttachment().vm.importCalendar(calendar)()

		expect(importCalendarEvent).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledWith('Could not create event')
	})

	it('saves the attachment of its own message, not of the message in the route', async () => {
		const view = shallowMount(MessageAttachment, {
			props: {
				id: '2',
				messageId: 41,
				fileName: 'price list.pdf',
				url: '/apps/mail/api/messages/41/attachment/2',
				size: 100,
				mime: 'application/pdf',
				mimeUrl: '',
			},
			global: {
				mixins: [Nextcloud],
				mocks: { $route: { params: { threadId: 40 } } },
			},
		})

		await view.vm.saveToCloud()

		expect(saveAttachmentToFiles).toHaveBeenCalledWith(41, '2', '/Documents')
	})
})
