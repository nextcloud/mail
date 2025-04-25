/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { FOLLOW_UP_TAG_LABEL } from '../constants.js'
import { getCalendarHome } from '../../service/caldavService.js'
import toCalendar from '../../util/calendar.js'

export default function mainStore() {
	return {
		getAccounts: (state) => {
			return state.accountList.map((id) => state.accountsUnmapped[id])
		},
		composerMessage: (state) => {
			return state.newMessage
		},
		composerMessageOptions: (state) => {
			return state.newMessage?.options
		},
		getTags: (state) => {
			return state.tagList.map(tagId => state.tags[tagId])
		},
		getFollowUpTag: (state) => {
			return Object.values(state.tags).find((tag) => tag.imapLabel === FOLLOW_UP_TAG_LABEL)
		},
		getFollowUpReminderEnvelopes: (state) => {
			return Object.values(state.envelopes)
				.filter((envelope) => envelope.tags
					?.map((tagId) => state.tags[tagId])
					.some((tag) => tag.imapLabel === FOLLOW_UP_TAG_LABEL),
				)
		},
		getCurrentUserPrincipal: (state) => state.currentUserPrincipal,
		getCurrentUserPrincipalEmail: (state) => state.currentUserPrincipal?.email,
		getCalendars: (state) => state.calendars,
		getClonedWriteableCalendars: (state) => state.calendars.filter(calendar => {
			return calendar.isWriteable()
		}).map(calendar => {
			// Hack: We need to clone all calendars because some methods (e.g. calendarQuery) are
			// unnecessarily mutating the object and causing vue warnings (if used outside of
			// mutations).
			const resourcetype = calendar.resourcetype.find(type => type !== '{DAV:}collection')
			const calendarHome = getCalendarHome()
			return new calendarHome._collectionFactoryMapper[resourcetype](
				calendarHome,
				calendar._request,
				calendar._url,
				calendar._props,
			)
		}),
		getSmimeCertificates: (state) => state.smimeCertificates,
		getTaskCalendarsForCurrentUser: state => {
			return state.calendars.filter(calendar => {
				return calendar.components.includes('VTODO') && calendar.currentUserPrivilegeSet.includes('{DAV:}write')
			}).map(calendar => toCalendar(calendar))
		},
		getNcVersion: (state) => state.preferences?.ncVersion,
		getAppVersion: (state) => state.preferences?.version,

		isOneLineLayout: (state) => state.list,
		getInternalAddresses: (state) => state.internalAddress?.filter(internalAddress => internalAddress !== undefined),
		getMailboxesAndSubmailboxesByAccountId: (state) => (accountId) => Object.values(state.mailboxes).filter(mailbox => mailbox.accountId === accountId),
	}
}
