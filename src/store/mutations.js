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
import sortedUniqBy from 'lodash/fp/sortedUniqBy'
import Vue from 'vue'

import { buildMailboxHierarchy } from '../imap/MailboxHierarchy'
import { havePrefix } from '../imap/MailboxPrefix'
import { normalizedFolderId, normalizedMessageId, normalizedEnvelopeListId } from './normalization'
import { sortMailboxes } from '../imap/MailboxSorter'
import { UNIFIED_ACCOUNT_ID } from './constants'

const addFolderToState = (state, account) => (folder) => {
	const id = normalizedFolderId(account.id, folder.id)
	folder.accountId = account.id
	folder.envelopeLists = {}
	Vue.set(state.folders, id, folder)
	return id
}

const sortAccounts = (accounts) => {
	accounts.sort((a1, a2) => a1.order - a2.order)
	return accounts
}

export default {
	savePreference(state, { key, value }) {
		Vue.set(state.preferences, key, value)
	},
	addAccount(state, account) {
		account.collapsed = true
		Vue.set(state.accounts, account.id, account)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.concat([account.id]).map((id) => state.accounts[id])).map((a) => a.id)
		)

		// Save the folders to the store, but only keep IDs in the account's folder list
		const folders = buildMailboxHierarchy(sortMailboxes(account.folders || []), havePrefix(account.folders))
		Vue.set(account, 'folders', [])
		const addToState = addFolderToState(state, account)
		folders.forEach((folder) => {
			// Add all folders (including subfolders to state, but only toplevel to account
			const id = addToState(folder)
			Vue.set(folder, 'folders', folder.folders.map(addToState))

			account.folders.push(id)
		})
	},
	editAccount(state, account) {
		Vue.set(state.accounts, account.id, Object.assign({}, state.accounts[account.id], account))
	},
	patchAccount(state, { account, data }) {
		Vue.set(state.accounts, account.id, Object.assign({}, state.accounts[account.id], data))
	},
	saveAccountsOrder(state, { account, order }) {
		Vue.set(account, 'order', order)
		Vue.set(
			state,
			'accountList',
			sortAccounts(state.accountList.map((id) => state.accounts[id])).map((a) => a.id)
		)
	},
	toggleAccountCollapsed(state, accountId) {
		state.accounts[accountId].collapsed = !state.accounts[accountId].collapsed
	},
	expandAccount(state, accountId) {
		state.accounts[accountId].collapsed = false
	},
	addFolder(state, { account, folder }) {
		// Flatten the existing ones before updating the hierarchy
		const existing = account.folders.map((id) => state.folders[id])
		existing.forEach((folder) => {
			if (!folder.folders) {
				return
			}
			folder.folders.map((id) => existing.push(state.folders[id]))
			folder.folders = []
		})
		// Save the folders to the store, but only keep IDs in the account's folder list
		existing.push(folder)
		const folders = buildMailboxHierarchy(sortMailboxes(existing), havePrefix(existing))
		Vue.set(account, 'folders', [])
		const addToState = addFolderToState(state, account)
		folders.forEach((folder) => {
			// Add all folders (including subfolders to state, but only toplevel to account
			const id = addToState(folder)
			Vue.set(folder, 'folders', folder.folders.map(addToState))

			account.folders.push(id)
		})
	},
	removeFolder(state, { accountId, folderId }) {
		const account = state.accounts[accountId]
		const id = normalizedFolderId(accountId, folderId)
		Vue.delete(state.folders, id)
		account.folders = account.folders.filter((fId) => fId !== id)
		account.folders.forEach((fId) => {
			const folder = state.folders[fId]
			if (folder.folders) {
				folder.folders = folder.folders.filter((fId) => fId !== id)
			}
		})
	},
	addEnvelope(state, { accountId, folderId, query, envelope }) {
		const folder = state.folders[normalizedFolderId(accountId, folderId)]
		Vue.set(state.envelopes, envelope.uuid, envelope)
		const listId = normalizedEnvelopeListId(query)
		const existing = folder.envelopeLists[listId] || []
		const uuidToDateInt = (uuid) => state.envelopes[uuid].dateInt
		const sortedUniqByDateInt = sortedUniqBy(uuidToDateInt)
		const orderByDateInt = orderBy(uuidToDateInt, 'desc')
		Vue.set(folder.envelopeLists, listId, sortedUniqByDateInt(orderByDateInt(existing.concat([envelope.uuid]))))

		const unifiedAccount = state.accounts[UNIFIED_ACCOUNT_ID]
		unifiedAccount.folders
			.map((fId) => state.folders[fId])
			.filter((f) => f.specialRole && f.specialRole === folder.specialRole)
			.forEach((folder) => {
				const existing = folder.envelopeLists[listId] || []
				Vue.set(
					folder.envelopeLists,
					listId,
					sortedUniqByDateInt(orderByDateInt(existing.concat([envelope.uuid])))
				)
			})
	},
	updateEnvelope(state, { envelope }) {
		const existing = state.envelopes[envelope.uid]
		if (!existing) {
			return
		}
		Vue.set(existing, 'flags', envelope.flags)
	},
	flagEnvelope(state, { envelope, flag, value }) {
		envelope.flags[flag] = value
	},
	removeEnvelope(state, { accountId, folderId, uid }) {
		const folder = state.folders[normalizedFolderId(accountId, folderId)]
		for (const listId in folder.envelopeLists) {
			if (!Object.hasOwnProperty.call(folder.envelopeLists, listId)) {
				continue
			}
			const list = folder.envelopeLists[listId]
			const idx = list.indexOf(normalizedMessageId(accountId, folderId, uid))
			if (idx < 0) {
				continue
			}
			console.debug('envelope removed from mailbox', accountId, folder.id, uid, listId)
			list.splice(idx, 1)
		}

		state.accounts[UNIFIED_ACCOUNT_ID].folders
			.map((fId) => state.folders[fId])
			.filter((f) => f.specialRole && f.specialRole === folder.specialRole)
			.forEach((folder) => {
				for (const listId in folder.envelopeLists) {
					if (!Object.hasOwnProperty.call(folder.envelopeLists, listId)) {
						continue
					}
					const list = folder.envelopeLists[listId]
					const idx = list.indexOf(normalizedMessageId(accountId, folderId, uid))
					if (idx < 0) {
						console.warn(
							'envelope does not exist in unified mailbox',
							accountId,
							folder.id,
							uid,
							listId,
							list
						)
						continue
					}
					console.debug('envelope removed from unified mailbox', accountId, folder.id, uid, listId)
					list.splice(idx, 1)
				}
			})
	},
	addMessage(state, { accountId, folderId, message }) {
		const uuid = normalizedMessageId(accountId, folderId, message.uid)
		message.accountId = accountId
		message.folderId = folderId
		message.uuid = uuid
		Vue.set(state.messages, uuid, message)
	},
	updateDraft(state, { draft, data, newUid }) {
		// Update draft's UID
		const oldUid = draft.uid
		const uid = normalizedMessageId(draft.accountId, draft.folderId, newUid)
		console.debug('saving draft as UID ' + uid)
		draft.uid = uid

		// TODO: strategy to keep the full draft object in sync, not just the visible
		//       changes
		draft.subject = data.subject

		// Update ref in folder's envelope list
		const envs = state.folders[normalizedFolderId(draft.accountId, draft.folderId)].envelopes
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
	removeMessage(state, { accountId, folderId, uid }) {
		Vue.delete(state.messages, normalizedMessageId(accountId, folderId, uid))
	},
	createAlias(state, { account, alias }) {
		account.aliases.push(alias)
	},
	deleteAlias(state, { account, alias }) {
		account.aliases.splice(account.aliases.indexOf(alias), 1)
	},
	changeFolderSubscription(state, { accountId, folderId, isSubscribed }) {
		const uid = normalizedFolderId(accountId, folderId)
		const folder = state.folders[uid]
		const attributes = folder.attributes
		if (isSubscribed) {
			if (!folder.attributes.includes('\\subscribed')) {
				attributes.push('\\subscribed')
			}
		} else {
			if (folder.attributes.includes('\\subscribed')) {
				attributes.splice(attributes.indexOf('\\subscribed'), 1)
			}
		}
		Vue.set(state.folders[uid], 'attributes', attributes)
	},
}
