/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import Vue from 'vue'
import Vuex from 'vuex'

import {
	PRIORITY_INBOX_ID,
	UNIFIED_ACCOUNT_ID,
	UNIFIED_INBOX_ID,
} from './constants'
import actions from './actions'
import { getters } from './getters'
import mutations from './mutations'
import { normalizedEnvelopeListId } from './normalization'
import orderBy from 'lodash/fp/orderBy'
Vue.use(Vuex)

const moduleThreads = {
	state: {
		lists: {
			[UNIFIED_INBOX_ID]: {},
			[PRIORITY_INBOX_ID]: {},
		},
	},
	mutations: {
		addAccount(state, account) {
			const mailboxes = account.mailboxes || []
			mailboxes.map(mailboxId => Vue.set(state.lists, mailboxId, {}))
		},
		addMailbox(state, { mailbox }) {
			Vue.set(state.lists, mailbox.databaseId, {})
		},
		addThread(state, { mailboxId, query, envelope }) {
			const listId = normalizedEnvelopeListId(query)
			const list = state.lists[mailboxId][listId] || []

			const index = list.findIndex((item) => item.threadRootId === envelope.threadRootId)
			const item = { threadRootId: envelope.threadRootId, messageId: envelope.databaseId, dateInt: envelope.dateInt }

			if (index === -1) {
				list.push(item)
			} else {
				list[index] = item
			}

			orderBy(list, 'dateInt', 'desc')
			Vue.set(state.lists[mailboxId], listId, list)
		},
		removeThread(state, { mailboxId, envelopeId }) {
			const lists = state.lists[mailboxId]
			const removeEnvelopeById = (item) => item.messageId !== envelopeId

			for (let list in lists) {
				list = list.filter(removeEnvelopeById)
			}

			Vue.set(state.lists, mailboxId, lists)
		},
	},
	actions: {},
	getters: {
		getThreads: (state, getters, rootState) => (mailboxId, query) => {
			const listId = normalizedEnvelopeListId(query)
			const list = state.lists[mailboxId][listId] || []

			return list.map((item) => rootState.envelopes[item.messageId])
		},
	},
}

export default new Vuex.Store({
	modules: {
		threads: moduleThreads,
	},
	strict: process.env.NODE_ENV !== 'production',
	state: {
		preferences: {},
		accounts: {
			[UNIFIED_ACCOUNT_ID]: {
				id: UNIFIED_ACCOUNT_ID,
				accountId: UNIFIED_ACCOUNT_ID,
				isUnified: true,
				mailboxes: [PRIORITY_INBOX_ID, UNIFIED_INBOX_ID],
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
		},
		envelopes: {},
		messages: {},
		autocompleteEntries: [],
		tags: {},
		tagList: [],
	},
	getters,
	mutations,
	actions,
})
