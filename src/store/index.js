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
	UNIFIED_ACCOUNT_ID,
	UNIFIED_INBOX_ID,
	PRIORITY_INBOX_ID,
} from './constants'
import actions from './actions'
import { getters } from './getters'
import mutations from './mutations'

Vue.use(Vuex)

export default new Vuex.Store({
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
	},
	getters,
	mutations,
	actions,
})
