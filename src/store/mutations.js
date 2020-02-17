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

import orderBy from 'lodash/fp/orderBy'
import sortedUniq from 'lodash/fp/sortedUniq'
import Vue from 'vue'

import {buildMailboxHierarchy} from '../imap/MailboxHierarchy'
import {havePrefix} from '../imap/MailboxPrefix'
import {sortMailboxes} from '../imap/MailboxSorter'
import {UNIFIED_ACCOUNT_ID} from './constants'

const addFolderToState = (state, account) => folder => {
	const id = account.id + '-' + folder.id
	folder.accountId = account.id
	folder.envelopes = []
	folder.searchEnvelopes = []
	Vue.set(state.folders, id, folder)
	return id
}

const sortAccounts = accounts => {
	accounts.sort((a1, a2) => a1.order - a2.order)
	return accounts
}

export default {
	savePreference(state, {key, value}) {
		Vue.set(state.preferences, key, value)
	},
	addAccount(state, account) {
		account.collapsed = true
		Vue.set(state.accounts, account.id, account)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.concat([account.id]).map(id => state.accounts[id])).map(a => a.id)
		)

		// Save the folders to the store, but only keep IDs in the account's folder list
		const folders = buildMailboxHierarchy(sortMailboxes(account.folders || []), havePrefix(account.folders))
		Vue.set(account, 'folders', [])
		const addToState = addFolderToState(state, account)
		folders.forEach(folder => {
			// Add all folders (including subfolders to state, but only toplevel to account
			const id = addToState(folder)
			Vue.set(folder, 'folders', folder.folders.map(addToState))

			account.folders.push(id)
		})
	},
	editAccount(state, account) {
		Vue.set(state.accounts, account.id, Object.assign({}, state.accounts[account.id], account))
	},
	saveAccountsOrder(state, {account, order}) {
		Vue.set(account, 'order', order)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.map(id => state.accounts[id])).map(a => a.id)
		)
	},
	toggleAccountCollapsed(state, accountId) {
		state.accounts[accountId].collapsed = !state.accounts[accountId].collapsed
	},
	addFolder(state, {account, folder}) {
		// Flatten the existing ones before updating the hierarchy
		const existing = account.folders.map(id => state.folders[id])
		existing.forEach(folder => {
			if (!folder.folders) {
				return
			}
			folder.folders.map(id => existing.push(state.folders[id]))
			folder.folders = []
		})
		// Save the folders to the store, but only keep IDs in the account's folder list
		existing.push(folder)
		const folders = buildMailboxHierarchy(sortMailboxes(existing), havePrefix(existing))
		Vue.set(account, 'folders', [])
		const addToState = addFolderToState(state, account)
		folders.forEach(folder => {
			// Add all folders (including subfolders to state, but only toplevel to account
			const id = addToState(folder)
			Vue.set(folder, 'folders', folder.folders.map(addToState))

			account.folders.push(id)
		})
	},
	addEnvelope(state, {accountId, folderId, envelope}) {
		const uid = accountId + '-' + folderId + '-' + envelope.id
		envelope.accountId = accountId
		envelope.folderId = folderId
		envelope.uid = uid
		Vue.set(state.envelopes, uid, envelope)
	},
	flagEnvelope(state, {envelope, flag, value}) {
		envelope.flags[flag] = value
	},
	removeEnvelope(state, {accountId, folder, id}) {
		const envelopeUid = accountId + '-' + folder.id + '-' + id
		const idx = folder.envelopes.indexOf(envelopeUid)
		if (idx < 0) {
			console.warn('envelope does not exist', accountId, folder.id, id)
			return
		}
		folder.envelopes.splice(idx, 1)

		const unifiedAccount = state.accounts[UNIFIED_ACCOUNT_ID]
		unifiedAccount.folders
			.map(fId => state.folders[fId])
			.filter(f => f.specialRole === folder.specialRole)
			.forEach(folder => {
				const idx = folder.envelopes.indexOf(envelopeUid)
				if (idx < 0) {
					console.warn('envelope does not exist in unified mailbox', accountId, folder.id, id)
					return
				}
				folder.envelopes.splice(idx, 1)
			})

		Vue.delete(folder.envelopes, envelopeUid)
	},
	addMessage(state, {accountId, folderId, message}) {
		const uid = accountId + '-' + folderId + '-' + message.id
		message.accountId = accountId
		message.folderId = folderId
		message.uid = uid
		Vue.set(state.messages, uid, message)
	},
	updateDraft(state, {draft, data, newUid}) {
		// Update draft's UID
		const oldUid = draft.uid
		const uid = draft.accountId + '-' + draft.folderId + '-' + newUid
		console.debug('saving draft as UID ' + uid)
		draft.uid = uid

		// TODO: strategy to keep the full draft object in sync, not just the visible
		//       changes
		draft.subject = data.subject

		// Update ref in folder's envelope list
		const envs = state.folders[draft.accountId + '-' + draft.folderId].envelopes
		const idx = envs.indexOf(oldUid)
		if (idx < 0) {
			console.warn('not replacing draft ' + oldUid + ' in envelope list because it did not exist')
		} else {
			envs[idx] = uid
		}

		// Move message/envelope objects to new keys
		Vue.delete(state.envelopes, oldUid)
		Vue.delete(state.messages, oldUid)
		Vue.set(state.envelopes, uid, draft)
		Vue.set(state.messages, uid, draft)
	},
	removeMessage(state, {accountId, folderId, id}) {
		Vue.delete(state.messages, accountId + '-' + folderId + '-' + id)
	},
}
