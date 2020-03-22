/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import {defaultTo, head} from 'ramda'

import {UNIFIED_ACCOUNT_ID} from './constants'
import {normalizedEnvelopeListId, normalizedFolderId, normalizedMessageId} from './normalization'

export const getters = {
	getPreference: (state) => (key, def) => {
		return defaultTo(def, state.preferences[key])
	},
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	accounts: (state) => {
		return state.accountList.map((id) => state.accounts[id])
	},
	getFolder: (state) => (accountId, folderId) => {
		return state.folders[normalizedFolderId(accountId, folderId)]
	},
	getFolders: (state) => (accountId) => {
		return state.accounts[accountId].folders.map((folderId) => state.folders[folderId])
	},
	getSubfolders: (state, getters) => (accountId, folderId) => {
		const folder = getters.getFolder(accountId, folderId)

		return folder.folders.map((id) => state.folders[id])
	},
	getUnifiedFolder: (state) => (specialRole) => {
		return head(
			state.accounts[UNIFIED_ACCOUNT_ID].folders
				.map((folderId) => state.folders[folderId])
				.filter((folder) => folder.specialRole === specialRole)
		)
	},
	getEnvelope: (state) => (accountId, folderId, id) => {
		return state.envelopes[normalizedMessageId(accountId, folderId, id)]
	},
	getEnvelopeById: (state) => (id) => {
		return state.envelopes[id]
	},
	getEnvelopes: (state, getters) => (accountId, folderId, query) => {
		const list = getters.getFolder(accountId, folderId).envelopeLists[normalizedEnvelopeListId(query)] || []
		return list.map((msgId) => state.envelopes[msgId])
	},
	getMessage: (state) => (accountId, folderId, id) => {
		return state.messages[normalizedMessageId(accountId, folderId, id)]
	},
}
