/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'

import {
	UNIFIED_ACCOUNT_ID,
	UNIFIED_INBOX_ID,
	PRIORITY_INBOX_ID,
	FOLLOW_UP_MAILBOX_ID,
} from './constants.js'
import actions from './actions.js'
import { getters } from './getters.js'
import mutations from './mutations.js'

Vue.use(Vuex)

export default new Store({
	strict: process.env.NODE_ENV !== 'production',
	modules: {
		root: {
			namespaced: false,
			state: {
				isExpiredSession: false,
				preferences: {},
				accounts: {
					[UNIFIED_ACCOUNT_ID]: {
						id: UNIFIED_ACCOUNT_ID,
						accountId: UNIFIED_ACCOUNT_ID,
						isUnified: true,
						mailboxes: [PRIORITY_INBOX_ID, UNIFIED_INBOX_ID, FOLLOW_UP_MAILBOX_ID],
						aliases: [],
						collapsed: false,
						emailAddress: '',
						name: '',
						showSubscribedOnly: false,
						signatureAboveQuote: false,
					},
				},
				accountList: [UNIFIED_ACCOUNT_ID],
				allAccountSettings: [],
				mailboxes: {
					[UNIFIED_INBOX_ID]: {
						id: UNIFIED_INBOX_ID,
						databaseId: UNIFIED_INBOX_ID,
						accountId: 0,
						attributes: ['\\subscribed'],
						isUnified: true,
						path: '',
						specialUse: ['inbox'],
						specialRole: 'inbox',
						unread: 0,
						mailboxes: [],
						envelopeLists: {},
						name: 'UNIFIED INBOX',
					},
					[PRIORITY_INBOX_ID]: {
						id: PRIORITY_INBOX_ID,
						databaseId: PRIORITY_INBOX_ID,
						accountId: 0,
						attributes: ['\\subscribed'],
						isPriorityInbox: true,
						path: '',
						specialUse: ['inbox'],
						specialRole: 'inbox',
						unread: 0,
						mailboxes: [],
						envelopeLists: {},
						name: 'PRIORITY INBOX',
					},
					[FOLLOW_UP_MAILBOX_ID]: {
						id: FOLLOW_UP_MAILBOX_ID,
						databaseId: FOLLOW_UP_MAILBOX_ID,
						accountId: 0,
						attributes: ['\\subscribed'],
						isUnified: true,
						path: '',
						specialUse: ['sent'],
						specialRole: 'sent',
						unread: 0,
						mailboxes: [],
						envelopeLists: {},
						name: 'FOLLOW UP REMINDERS',
					},
				},
				envelopes: {},
				messages: {},
				newMessage: undefined,
				showMessageComposer: false,
				composerMessageIsSaved: false,
				composerSessionId: undefined,
				nextComposerSessionId: 1,
				autocompleteEntries: [],
				tags: {},
				tagList: [],
				isScheduledSendingDisabled: false,
				isSnoozeDisabled: false,
				currentUserPrincipal: undefined,
				googleOauthUrl: null,
				masterPasswordEnabled: false,
				sieveScript: {},
				calendars: [],
				addressBooks: [],
				smimeCertificates: [],
				hasFetchedInitialEnvelopes: false,
				followUpFeatureAvailable: false,
				internalAddress: [],
			},
			getters,
			mutations,
			actions,
		},
	},
})
