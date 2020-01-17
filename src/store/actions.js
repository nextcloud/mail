/**
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

import flatMapDeep from 'lodash/fp/flatMapDeep'
import flatten from 'lodash/fp/flatten'
import flattenDepth from 'lodash/fp/flattenDepth'
import identity from 'lodash/fp/identity'
import isEmpty from 'lodash/fp/isEmpty'
import last from 'lodash/fp/last'
import orderBy from 'lodash/fp/orderBy'
import slice from 'lodash/fp/slice'
import sortedUniq from 'lodash/fp/sortedUniq'

import {savePreference} from '../service/PreferenceService'
import {
	create as createAccount,
	update as updateAccount,
	patch as patchAccount,
	updateSignature,
	deleteAccount,
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts,
} from '../service/AccountService'
import {fetchAll as fetchAllFolders, create as createFolder, markFolderRead} from '../service/FolderService'
import {deleteMessage, fetchEnvelopes, fetchMessage, setEnvelopeFlag, syncEnvelopes} from '../service/MessageService'
import logger from '../logger'
import {showNewMessagesNotification} from '../service/NotificationService'
import {parseUid} from '../util/EnvelopeUidParser'

export default {
	savePreference({commit, getters}, {key, value}) {
		return savePreference(key, value).then(({value}) => {
			commit('savePreference', {
				key,
				value,
			})
		})
	},
	fetchAccounts({commit, getters}) {
		return fetchAllAccounts().then(accounts => {
			accounts.forEach(account => commit('addAccount', account))
			return getters.accounts
		})
	},
	fetchAccount({commit}, id) {
		return fetchAccount(id).then(account => {
			commit('addAccount', account)
			return account
		})
	},
	createAccount({commit}, config) {
		return createAccount(config).then(account => {
			logger.debug(`account ${account.id} created, fetching folders …`, account)
			return fetchAllFolders(account.id)
				.then(folders => {
					account.folders = folders
					commit('addAccount', account)
				})
				.then(() => console.info("new account's folders fetched"))
				.then(() => account)
		})
	},
	updateAccount({commit}, config) {
		return updateAccount(config).then(account => {
			console.debug('account updated', account)
			commit('editAccount', account)
			return account
		})
	},
	patchAccount({commit}, {account, data}) {
		return patchAccount(account, data).then(account => {
			console.debug('account patched', account, data)
			commit('editAccount', data)
			return account
		})
	},
	updateAccountSignature({commit}, {account, signature}) {
		return updateSignature(account, signature).then(() => {
			console.debug('account signature updated')
			const updated = Object.assign({}, account, {signature})
			commit('editAccount', updated)
			return account
		})
	},
	deleteAccount({commit}, account) {
		return deleteAccount(account.id).catch(err => {
			console.error('could not delete account', err)
			throw err
		})
	},
	createFolder({commit}, {account, name}) {
		return createFolder(account.id, name).then(folder => {
			console.debug(`folder ${name} created for account ${account.id}`, {folder})
			commit('addFolder', {account, folder})
		})
	},
	moveAccount({commit, getters}, {account, up}) {
		const accounts = getters.accounts
		const index = accounts.indexOf(account)
		if (up) {
			const previous = accounts[index - 1]
			accounts[index - 1] = account
			accounts[index] = previous
		} else {
			const next = accounts[index + 1]
			accounts[index + 1] = account
			accounts[index] = next
		}
		return Promise.all(
			accounts.map((account, idx) => {
				if (account.id === 0) {
					return
				}
				commit('saveAccountsOrder', {account, order: idx})
				return patchAccount(account, {order: idx})
			})
		)
	},
	markFolderRead({dispatch}, {account, folderId}) {
		return markFolderRead(account.id, folderId).then(
			dispatch('syncEnvelopes', {
				accountId: account.id,
				folderId: folderId,
			})
		)
	},
	fetchEnvelopes({state, commit, getters, dispatch}, {accountId, folderId, query}) {
		const folder = getters.getFolder(accountId, folderId)
		const isSearch = query !== undefined
		if (folder.isUnified) {
			// Fetch and combine envelopes of all individual folders
			//
			// The last envelope is excluded to efficiently build the next unified
			// pages (fetch only individual pages that do not have sufficient local
			// data)
			//
			// TODO: handle short/ending streams and show their last element as well
			return Promise.all(
				getters.accounts
					.filter(account => !account.isUnified)
					.map(account =>
						Promise.all(
							getters
								.getFolders(account.id)
								.filter(f => f.specialRole === folder.specialRole)
								.map(folder =>
									dispatch('fetchEnvelopes', {
										accountId: account.id,
										folderId: folder.id,
										query,
									})
								)
						)
					)
			)
				.then(res => res.map(envs => envs.slice(0, 19)))
				.then(res => flattenDepth(2)(res))
				.then(envs => orderBy(env => env.dateInt)('desc')(envs))
				.then(envs => slice(0)(19)(envs)) // 19 to handle non-overlapping streams
				.then(envelopes => {
					if (!isSearch) {
						commit('addUnifiedEnvelopes', {
							folder,
							uids: envelopes.map(e => e.uid),
						})
					} else {
						commit('addUnifiedSearchEnvelopes', {
							folder,
							uids: envelopes.map(e => e.uid),
						})
					}
					return envelopes
				})
		}

		return fetchEnvelopes(accountId, folderId, query).then(envelopes => {
			let folder = getters.getFolder(accountId, folderId)

			if (!isSearch) {
				envelopes.forEach(envelope =>
					commit('addEnvelope', {
						accountId,
						folder,
						envelope,
					})
				)
			} else {
				commit('addSearchEnvelopes', {
					accountId,
					folder,
					envelopes,
					clear: true,
				})
			}
			return envelopes
		})
	},
	fetchNextUnifiedEnvelopePage({state, commit, getters, dispatch}, {accountId, folderId, query}) {
		const folder = getters.getFolder(accountId, folderId)
		const isSearch = query !== undefined
		const list = isSearch ? 'searchEnvelopes' : 'envelopes'

		// We only care about folders of the same type/role
		const individualFolders = flatten(
			getters.accounts
				.filter(a => !a.isUnified)
				.map(a => getters.getFolders(a.id).filter(f => f.specialRole === folder.specialRole))
		)
		// Build a sorted list of all currently known envelopes (except last elem)
		const knownEnvelopes = orderBy(id => state.envelopes[id].dateInt)('desc')(
			flatten(individualFolders.map(f => f[list].slice(0, f[list].length - 1)))
		)
		// The index of the last element in the current unified mailbox determines
		// the new offset
		const tailId = last(folder[list])
		const tailIdx = knownEnvelopes.indexOf(tailId)
		if (tailIdx === -1) {
			return Promise.reject(
				new Error('current envelopes do not contain unified mailbox tail. this should not have happened')
			)
		}

		// Select the next page, based on offline data
		const nextCandidates = knownEnvelopes.slice(tailIdx + 1, tailIdx + 20)

		// Now, let's check if any of the "streams" have reached their ends.
		// In that case, we attempt to fetch more elements recursively
		//
		// In case of an empty next page we always fetch all streams (this might be redundant)
		//
		// Their end was reached if the last known (oldest) envelope is an element
		// of the offline page
		// TODO: what about streams that ended before? Is it safe to ignore those?
		const needFetch = individualFolders
			.filter(f => !isEmpty(f[list]))
			.filter(f => {
				const lastShown = f[list][f[list].length - 2]
				return nextCandidates.length <= 18 || nextCandidates.indexOf(lastShown) !== -1
			})

		if (isEmpty(needFetch)) {
			if (!isSearch) {
				commit('addUnifiedEnvelopes', {
					folder,
					uids: sortedUniq(
						orderBy(id => state.envelopes[id].dateInt)('desc')(folder[list].concat(nextCandidates))
					),
				})
			} else {
				commit('addUnifiedSearchEnvelopes', {
					folder,
					uids: sortedUniq(
						orderBy(id => state.envelopes[id].dateInt)('desc')(folder[list].concat(nextCandidates))
					),
				})
			}
		} else {
			return Promise.all(
				needFetch.map(f =>
					dispatch('fetchNextEnvelopePage', {
						accountId: f.accountId,
						folderId: f.id,
						query,
					})
				)
			).then(() => {
				return dispatch('fetchNextUnifiedEnvelopePage', {
					accountId,
					folderId,
					query,
				})
			})
		}
	},
	fetchNextEnvelopePage({commit, getters, dispatch}, {accountId, folderId, query}) {
		const folder = getters.getFolder(accountId, folderId)
		const isSearch = query !== undefined
		const list = isSearch ? 'searchEnvelopes' : 'envelopes'

		if (folder.isUnified) {
			return dispatch('fetchNextUnifiedEnvelopePage', {
				accountId,
				folderId,
				query,
			})
		}

		const lastEnvelopeId = folder[list][folder.envelopes.length - 1]
		if (typeof lastEnvelopeId === 'undefined') {
			console.error('folder is empty', folder[list])
			return Promise.reject(new Error('Local folder has no envelopes, cannot determine cursor'))
		}
		const lastEnvelope = getters.getEnvelopeById(lastEnvelopeId)
		if (typeof lastEnvelope === 'undefined') {
			return Promise.reject(new Error('Cannot find last envelope. Required for the folder cursor'))
		}

		return fetchEnvelopes(accountId, folderId, query, lastEnvelope.dateInt).then(envelopes => {
			if (!isSearch) {
				envelopes.forEach(envelope =>
					commit('addEnvelope', {
						accountId,
						folder,
						envelope,
					})
				)
			} else {
				commit('addSearchEnvelopes', {
					accountId,
					folder,
					envelopes,
					clear: false,
				})
			}

			return envelopes
		})
	},
	syncEnvelopes({commit, getters, dispatch}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			return Promise.all(
				getters.accounts
					.filter(account => !account.isUnified)
					.map(account =>
						Promise.all(
							getters
								.getFolders(account.id)
								.filter(f => f.specialRole === folder.specialRole)
								.map(folder =>
									dispatch('syncEnvelopes', {
										accountId: account.id,
										folderId: folder.id,
									})
								)
						)
					)
			)
		}

		const syncToken = folder.syncToken
		const uids = getters.getEnvelopes(accountId, folderId).map(env => env.id)

		return syncEnvelopes(accountId, folderId, syncToken, uids).then(syncData => {
			const unifiedFolder = getters.getUnifiedFolder(folder.specialRole)

			syncData.newMessages.forEach(envelope => {
				commit('addEnvelope', {
					accountId,
					folder,
					envelope,
				})
				if (unifiedFolder) {
					commit('addUnifiedEnvelope', {
						folder: unifiedFolder,
						envelope,
					})
				}
			})
			syncData.changedMessages.forEach(envelope => {
				commit('addEnvelope', {
					accountId,
					folder,
					envelope,
				})
			})
			syncData.vanishedMessages.forEach(id => {
				commit('removeEnvelope', {
					accountId,
					folder,
					id,
				})
				// Already removed from unified inbox
			})
			commit('updateFolderSyncToken', {
				folder,
				syncToken: syncData.token,
			})

			return syncData.newMessages
		})
	},
	syncInboxes({getters, dispatch}) {
		return Promise.all(
			getters.accounts
				.filter(a => !a.isUnified)
				.map(account => {
					return Promise.all(
						getters.getFolders(account.id).map(folder => {
							if (folder.specialRole !== 'inbox') {
								return
							}

							return dispatch('syncEnvelopes', {
								accountId: account.id,
								folderId: folder.id,
							})
						})
					)
				})
		).then(results => {
			const newMessages = flatMapDeep(identity)(results).filter(m => m !== undefined)
			if (newMessages.length > 0) {
				showNewMessagesNotification(newMessages)
			}
		})
	},
	toggleEnvelopeFlagged({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.flagged
		commit('flagEnvelope', {
			envelope,
			flag: 'flagged',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'flagged', !oldState).catch(e => {
			console.error('could not toggle message flagged state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'flagged',
				value: oldState,
			})
		})
	},
	toggleEnvelopeSeen({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.unseen
		commit('flagEnvelope', {
			envelope,
			flag: 'unseen',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'unseen', !oldState).catch(e => {
			console.error('could not toggle message unseen state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'unseen',
				value: oldState,
			})
		})
	},
	fetchMessage({commit}, uid) {
		const {accountId, folderId, id} = parseUid(uid)
		return fetchMessage(accountId, folderId, id).then(message => {
			// Only commit if not undefined (not found)
			if (message) {
				commit('addMessage', {
					accountId,
					folderId,
					message,
				})
			}

			return message
		})
	},
	replaceDraft({getters, commit}, {draft, uid, data}) {
		commit('updateDraft', {
			draft,
			data,
			newUid: uid,
		})
	},
	deleteMessage({getters, commit}, envelope) {
		const folder = getters.getFolder(envelope.accountId, envelope.folderId)
		commit('removeEnvelope', {
			accountId: envelope.accountId,
			folder,
			id: envelope.id,
		})

		return deleteMessage(envelope.accountId, envelope.folderId, envelope.id)
			.then(() => {
				commit('removeMessage', {
					accountId: envelope.accountId,
					folder,
					id: envelope.id,
				})
				console.log('message removed')
			})
			.catch(err => {
				console.error('could not delete message', err)
				commit('addEnvelope', {
					accountId: envelope.accountId,
					folder,
					envelope,
				})
				throw err
			})
	},
}
