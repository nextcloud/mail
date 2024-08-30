/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defaultTo, head, prop, sortBy } from 'ramda'

import { FOLLOW_UP_TAG_LABEL, UNIFIED_ACCOUNT_ID } from './constants.js'
import { normalizedEnvelopeListId } from './normalization.js'
import { getCalendarHome } from '../service/caldavService.js'
import toCalendar from './calendar.js'

export const getters = {
	getPreference: (state) => (key, def) => {
		return defaultTo(def, state.preferences[key])
	},
	isExpiredSession: (state) => {
		return state.isExpiredSession
	},
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getAllAccountSettings: (state) => {
		return state.allAccountSettings
	},
	accounts: (state) => {
		return state.accountList.map((id) => state.accounts[id])
	},
	getMailbox: (state) => (id) => {
		return state.mailboxes[id]
	},
	getMailboxes: (state) => (accountId) => {
		return state.accounts[accountId].mailboxes.map((id) => state.mailboxes[id])
	},
	getSubMailboxes: (state, getters) => (id) => {
		const mailbox = getters.getMailbox(id)
		return mailbox.mailboxes.map((id) => state.mailboxes[id])
	},
	getParentMailbox: (state, getters) => (id) => {
		for (const mailbox of getters.getMailboxes(getters.getMailbox(id).accountId)) {
			if (mailbox.mailboxes.includes(id)) {
				return mailbox
			}
		}
		return undefined
	},
	getUnifiedMailbox: (state) => (specialRole) => {
		return head(
			state.accounts[UNIFIED_ACCOUNT_ID].mailboxes
				.map((id) => state.mailboxes[id])
				.filter((mailbox) => mailbox.specialRole === specialRole),
		)
	},
	showMessageComposer: (state) => {
		return state.showMessageComposer
	},
	composerMessage: (state) => {
		return state.newMessage
	},
	composerMessageOptions: (state) => {
		return state.newMessage?.options
	},
	composerMessageIsSaved: (state) => {
		return state.composerMessageIsSaved
	},
	composerSessionId: (state) => {
		return state.composerSessionId
	},
	getEnvelope: (state) => (id) => {
		return state.envelopes[id]
	},
	getEnvelopes: (state, getters) => (mailboxId, query) => {
		const list = getters.getMailbox(mailboxId).envelopeLists[normalizedEnvelopeListId(query)] || []
		return list.map((msgId) => state.envelopes[msgId])
	},
	getEnvelopesByThreadRootId: (state) => (accountId, threadRootId) => {
		return sortBy(
			prop('dateInt'),
			Object.values(state.envelopes).filter(envelope => envelope.accountId === accountId && envelope.threadRootId === threadRootId),
		)
	},
	getMessage: (state) => (id) => {
		return state.messages[id]
	},
	getEnvelopeThread: (state) => (id) => {
		const thread = state.envelopes[id]?.thread ?? []
		const envelopes = thread.map(id => state.envelopes[id])
		return sortBy(prop('dateInt'), envelopes)
	},
	getEnvelopeTags: (state) => (id) => {
		const tags = state.envelopes[id]?.tags ?? []
		return tags.map((tagId) => state.tags[tagId])
	},
	getTag: (state) => (id) => {
		return state.tags[id]
	},
	isInternalAddress: (state) => (address) => {
		const domain = address.split('@')[1]
		return state.internalAddress.some((internalAddress) => internalAddress.address === address || internalAddress.address === domain)
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
	isScheduledSendingDisabled: (state) => state.isScheduledSendingDisabled,
	isSnoozeDisabled: (state) => state.isSnoozeDisabled,
	googleOauthUrl: (state) => state.googleOauthUrl,
	masterPasswordEnabled: (state) => state.masterPasswordEnabled,
	microsoftOauthUrl: (state) => state.microsoftOauthUrl,
	getActiveSieveScript: (state) => (accountId) => state.sieveScript[accountId],
	getCurrentUserPrincipal: (state) => state.currentUserPrincipal,
	getCurrentUserPrincipalEmail: (state) => state.currentUserPrincipal?.email,
	getCalendars: (state) => state.calendars,
	getAddressBooks: (state) => state.addressBooks,
	getClonedCalendars: (state) => state.calendars.map(calendar => {
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
	getSmimeCertificate: (state) => (id) => state.smimeCertificates.find((cert) => cert.id === id),
	getSmimeCertificateByEmail: (state) => (email) => state.smimeCertificates.find((cert) => cert.emailAddress === email),
	getTaskCalendarsForCurrentUser: state => {
		return state.calendars.filter(calendar => {
			return calendar.components.includes('VTODO') && calendar.currentUserPrivilegeSet.includes('{DAV:}write')
		}).map(calendar => toCalendar(calendar))
	},
	getNcVersion: (state) => state.preferences?.ncVersion,
	getAppVersion: (state) => state.preferences?.version,
	findMailboxBySpecialRole: (state, getters) => (accountId, specialRole) => {
		return getters.getMailboxes(accountId).find(mailbox => mailbox.specialRole === specialRole)
	},
	findMailboxByName: (state, getters) => (accountId, name) => {
		return getters.getMailboxes(accountId).find(mailbox => mailbox.name === name)
	},
	getInbox: (state, getters) => (accountId) => {
		return getters.findMailboxBySpecialRole(accountId, 'inbox')
	},
	isOneLineLayout: (state) => state.list,
	hasFetchedInitialEnvelopes: (state) => state.hasFetchedInitialEnvelopes,
	isFollowUpFeatureAvailable: (state) => state.followUpFeatureAvailable,
	getInternalAddresses: (state) => state.internalAddress?.filter(internalAddress => internalAddress !== undefined),
}
