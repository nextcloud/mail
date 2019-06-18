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

import _ from 'lodash'
import {translate as t} from 'nextcloud-l10n'
import Vue from 'vue'
import Vuex from 'vuex'

import {value} from '../util/undefined'

import {UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID, UNIFIED_INBOX_UID} from './constants'
import actions from './actions'
import mutations from './mutations'

Vue.use(Vuex)

export const getters = {
	getPreference: state => (key, def) => {
		return value(state.preferences[key]).or(def)
	},
	getAccount: state => id => {
		return state.accounts[id]
	},
	getAccounts: state => () => {
		return state.accountList.map(id => state.accounts[id])
	},
	getFolder: state => (accountId, folderId) => {
		return state.folders[accountId + '-' + folderId]
	},
	getFolders: state => accountId => {
		return state.accounts[accountId].folders.map(folderId => state.folders[folderId])
	},
	getUnifiedFolder: state => specialRole => {
		return _.head(
			state.accounts[UNIFIED_ACCOUNT_ID].folders
				.map(folderId => state.folders[folderId])
				.filter(folder => folder.specialRole === specialRole)
		)
	},
	getEnvelope: state => (accountId, folderId, id) => {
		return state.envelopes[accountId + '-' + folderId + '-' + id]
	},
	getEnvelopeById: state => id => {
		return state.envelopes[id]
	},
	getEnvelopes: (state, getters) => (accountId, folderId) => {
		return getters.getFolder(accountId, folderId).envelopes.map(msgId => state.envelopes[msgId])
	},
	getSearchEnvelopes: (state, getters) => (accountId, folderId) => {
		return getters.getFolder(accountId, folderId).searchEnvelopes.map(msgId => state.envelopes[msgId])
	},
	getMessage: state => (accountId, folderId, id) => {
		return state.messages[accountId + '-' + folderId + '-' + id]
	},
	getMessageByUid: state => uid => {
		return state.messages[uid]
	},
}

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',
	state: {
		preferences: {},
		accounts: {
			[UNIFIED_ACCOUNT_ID]: {
				id: UNIFIED_ACCOUNT_ID,
				isUnified: true,
				folders: [UNIFIED_INBOX_UID],
				collapsed: false,
				emailAddress: '',
				name: '',
			},
		},
		accountList: [UNIFIED_ACCOUNT_ID],
		folders: {
			[UNIFIED_INBOX_UID]: {
				id: UNIFIED_INBOX_ID,
				accountId: 0,
				isUnified: true,
				specialUse: ['inbox'],
				specialRole: 'inbox',
				name: t('mail', 'All inboxes'), // TODO,
				unread: 0,
				folders: [],
				envelopes: [],
				searchEnvelopes: [],
			},
		},
		envelopes: {},
		messages: {},
		autocompleteEntries: [],
	},
	getters,
	mutations,
	actions,
})
