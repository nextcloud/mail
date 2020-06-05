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
import orderBy from 'lodash/fp/orderBy'
import {
	andThen,
	complement,
	curry,
	filter,
	flatten,
	gt,
	head,
	identity,
	last,
	map,
	pipe,
	prop,
	propEq,
	slice,
	tap,
	where,
} from 'ramda'

import {savePreference} from '../service/PreferenceService'
import {
	create as createAccount,
	deleteAccount,
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts,
	patch as patchAccount,
	update as updateAccount,
	updateSignature,
} from '../service/AccountService'
import {create as createFolder, fetchAll as fetchAllFolders, markFolderRead} from '../service/FolderService'
import {
	deleteMessage,
	fetchEnvelope,
	fetchEnvelopes,
	fetchMessage,
	setEnvelopeFlag,
	syncEnvelopes,
} from '../service/MessageService'
import logger from '../logger'
import {normalizedEnvelopeListId} from './normalization'
import {showNewMessagesNotification} from '../service/NotificationService'
import {parseUid} from '../util/EnvelopeUidParser'
import {matchError} from '../errors/match'
import SyncIncompleteError from '../errors/SyncIncompleteError'
import MailboxLockedError from '../errors/MailboxLockedError'
import {wait} from '../util/wait'
import {UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID} from './constants'

const PAGE_SIZE = 20

const sliceToPage = slice(0, PAGE_SIZE)

const findIndividualFolders = curry((getFolders, specialRole) =>
	pipe(
		filter(complement(prop('isUnified'))),
		map(prop('accountId')),
		map(getFolders),
		flatten,
		filter(propEq('specialRole', specialRole))
	)
)

const combineEnvelopeLists = pipe(flatten, orderBy(prop('dateInt'), 'desc'))

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
		return fetchAllAccounts().then((accounts) => {
			accounts.forEach((account) => commit('addAccount', account))
			return getters.accounts
		})
	},
	fetchAccount({commit}, id) {
		return fetchAccount(id).then((account) => {
			commit('addAccount', account)
			return account
		})
	},
	createAccount({commit}, config) {
		return createAccount(config).then((account) => {
			logger.debug(`account ${account.id} created, fetching folders …`, account)
			return fetchAllFolders(account.id)
				.then((folders) => {
					account.folders = folders
					commit('addAccount', account)
				})
				.then(() => console.info("new account's folders fetched"))
				.then(() => account)
		})
	},
	updateAccount({commit}, config) {
		return updateAccount(config).then((account) => {
			console.debug('account updated', account)
			commit('editAccount', account)
			return account
		})
	},
	patchAccount({commit}, {account, data}) {
		return patchAccount(account, data).then((account) => {
			console.debug('account patched', account, data)
			commit('patchAccount', {account, data})
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
		return deleteAccount(account.id).catch((err) => {
			console.error('could not delete account', err)
			throw err
		})
	},
	createFolder({commit}, {account, name}) {
		return createFolder(account.id, name).then((folder) => {
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
	markFolderRead({getters, dispatch}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			const findIndividual = findIndividualFolders(getters.getFolders, folder.specialRole)
			const individualFolders = findIndividual(getters.accounts)
			return Promise.all(
				individualFolders.map((f) =>
					dispatch('markFolderRead', {
						accountId: f.accountId,
						folderId: f.id,
					})
				)
			)
		}

		return markFolderRead(accountId, folderId).then(
			dispatch('syncEnvelopes', {
				accountId: accountId,
				folderId: folderId,
			})
		)
	},
	fetchEnvelope({commit, getters}, uid) {
		const {accountId, folderId, id} = parseUid(uid)

		const cached = getters.getEnvelope(accountId, folderId, id)
		if (cached) {
			logger.debug(`using cached value for envelope ${uid}`)
			return cached
		}

		return fetchEnvelope(accountId, folderId, id).then((envelope) => {
			// Only commit if not undefined (not found)
			if (envelope) {
				commit('addEnvelope', {
					accountId,
					folderId,
					envelope,
				})
			}

			// Always use the object from the store
			return getters.getEnvelope(accountId, folderId, id)
		})
	},
	fetchEnvelopes({state, commit, getters, dispatch}, {accountId, folderId, query}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			const fetchIndividualLists = pipe(
				map((f) =>
					dispatch('fetchEnvelopes', {
						accountId: f.accountId,
						folderId: f.id,
						query,
					})
				),
				Promise.all.bind(Promise),
				andThen(map(sliceToPage))
			)
			const fetchUnifiedEnvelopes = pipe(
				findIndividualFolders(getters.getFolders, folder.specialRole),
				fetchIndividualLists,
				andThen(combineEnvelopeLists),
				andThen(sliceToPage),
				andThen(
					tap(
						map((envelope) =>
							commit('addEnvelope', {
								accountId,
								folderId,
								envelope,
								query,
							})
						)
					)
				)
			)

			return fetchUnifiedEnvelopes(getters.accounts)
		}

		return pipe(
			fetchEnvelopes,
			andThen(
				tap(
					map((envelope) =>
						commit('addEnvelope', {
							accountId,
							folderId,
							query,
							envelope,
						})
					)
				)
			)
		)(accountId, folderId, query, undefined, PAGE_SIZE)
	},
	fetchNextEnvelopePage({commit, getters, dispatch}, {accountId, folderId, query, rec = true}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			const getIndivisualLists = curry((query, f) => getters.getEnvelopes(f.accountId, f.id, query))
			const individualCursor = curry((query, f) =>
				prop('dateInt', last(getters.getEnvelopes(f.accountId, f.id, query)))
			)
			const cursor = individualCursor(query, folder)

			if (cursor === undefined) {
				throw new Error('Unified list has no tail')
			}
			const nextLocalUnifiedEnvelopePage = pipe(
				findIndividualFolders(getters.getFolders, folder.specialRole),
				map(getIndivisualLists(query)),
				combineEnvelopeLists,
				filter(
					where({
						dateInt: gt(cursor),
					})
				),
				sliceToPage
			)
			// We know the next page based on local data
			// We have to fetch individual pages only if the page ends in the known
			// next page. If it ended before, there is no data to fetch anyway. If
			// it ends after, we have all the relevant data already
			const needsFetch = curry((query, nextPage, f) => {
				const c = individualCursor(query, f)
				return nextPage.length < PAGE_SIZE || (c <= head(nextPage).dateInt && c >= last(nextPage).dateInt)
			})

			const foldersToFetch = (accounts) =>
				pipe(
					findIndividualFolders(getters.getFolders, folder.specialRole),
					filter(needsFetch(query, nextLocalUnifiedEnvelopePage(accounts)))
				)(accounts)
			const fs = foldersToFetch(getters.accounts)

			if (rec && fs.length) {
				return pipe(
					map((f) =>
						dispatch('fetchNextEnvelopePage', {
							accountId: f.accountId,
							folderId: f.id,
							query,
						})
					),
					Promise.all.bind(Promise),
					andThen(() =>
						dispatch('fetchNextEnvelopePage', {
							accountId,
							folderId,
							query,
							rec: false,
						})
					)
				)(fs)
			}

			const page = nextLocalUnifiedEnvelopePage(getters.accounts)
			page.map((envelope) =>
				commit('addEnvelope', {
					accountId,
					folderId,
					query,
					envelope,
				})
			)
			return page
		}

		const list = folder.envelopeLists[normalizedEnvelopeListId(query)]
		if (list === undefined) {
			console.warn("envelope list is not defined, can't fetch next page", accountId, folderId, query)
			return Promise.resolve([])
		}
		const lastEnvelopeId = last(list)
		if (typeof lastEnvelopeId === 'undefined') {
			console.error('folder is empty', list)
			return Promise.reject(new Error('Local folder has no envelopes, cannot determine cursor'))
		}
		const lastEnvelope = getters.getEnvelopeById(lastEnvelopeId)
		if (typeof lastEnvelope === 'undefined') {
			return Promise.reject(new Error('Cannot find last envelope. Required for the folder cursor'))
		}

		return fetchEnvelopes(accountId, folderId, query, lastEnvelope.dateInt, PAGE_SIZE).then((envelopes) => {
			logger.debug(`fetched ${envelopes.length} messages for the next page of ${accountId}:${folderId}`, {
				envelopes,
			})
			envelopes.forEach((envelope) =>
				commit('addEnvelope', {
					accountId,
					folderId,
					query,
					envelope,
				})
			)
			return envelopes
		})
	},
	syncEnvelopes({commit, getters, dispatch}, {accountId, folderId, query, init = false}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			return Promise.all(
				getters.accounts
					.filter((account) => !account.isUnified)
					.map((account) =>
						Promise.all(
							getters
								.getFolders(account.id)
								.filter((f) => f.specialRole === folder.specialRole)
								.map((folder) =>
									dispatch('syncEnvelopes', {
										accountId: account.id,
										folderId: folder.id,
										query,
										init,
									})
								)
						)
					)
			)
		} else if (folder.isPriorityInbox && query === undefined) {
			return Promise.all(
				getters.accounts
					.filter((account) => !account.isUnified)
					.map((account) =>
						Promise.all(
							getters
								.getFolders(account.id)
								.filter((f) => f.specialRole === folder.specialRole)
								.map((folder) =>
									dispatch('syncEnvelopes', {
										accountId: account.id,
										folderId: folder.id,
										query,
										init,
									})
								)
						)
					)
			)
		}

		const uids = getters.getEnvelopes(accountId, folderId, query).map((env) => env.id)

		return syncEnvelopes(accountId, folderId, uids, query, init)
			.then((syncData) => {
				const unifiedFolder = getters.getUnifiedFolder(folder.specialRole)

				syncData.newMessages.forEach((envelope) => {
					commit('addEnvelope', {
						accountId,
						folderId,
						envelope,
						query,
					})
					if (unifiedFolder) {
						commit('updateEnvelope', {
							envelope,
						})
					}
				})
				syncData.changedMessages.forEach((envelope) => {
					commit('updateEnvelope', {
						envelope,
					})
				})
				syncData.vanishedMessages.forEach((id) => {
					commit('removeEnvelope', {
						accountId,
						folderId,
						id,
					})
					// Already removed from unified inbox
				})

				return syncData.newMessages
			})
			.catch((error) => {
				return matchError(error, {
					[SyncIncompleteError.getName()]() {
						console.warn('(initial) sync is incomplete, retriggering')
						return dispatch('syncEnvelopes', {accountId, folderId, query, init})
					},
					[MailboxLockedError.getName()](error) {
						logger.info('Sync failed because the mailbox is locked, retriggering', {error})
						return wait(1500).then(() => dispatch('syncEnvelopes', {accountId, folderId, query, init}))
					},
					default(error) {
						console.error('Could not sync envelopes: ' + error.message, error)
					},
				})
			})
	},
	async syncInboxes({getters, dispatch}) {
		const results = await Promise.all(
			getters.accounts
				.filter((a) => !a.isUnified)
				.map((account) => {
					return Promise.all(
						getters.getFolders(account.id).map(async (folder) => {
							if (folder.specialRole !== 'inbox') {
								return
							}

							const list = folder.envelopeLists[normalizedEnvelopeListId(undefined)]
							if (list === undefined) {
								await dispatch('fetchEnvelopes', {
									accountId: account.id,
									folderId: folder.id,
								})
							}

							return await dispatch('syncEnvelopes', {
								accountId: account.id,
								folderId: folder.id,
							})
						})
					)
				})
		)
		const newMessages = flatMapDeep(identity, results).filter((m) => m !== undefined)
		if (newMessages.length === 0) {
			return
		}

		try {
			// Make sure the priority inbox is updated as well
			logger.info('updating priority inbox')
			for (const query of ['is:important not:starred', 'is:starred not:important', 'not:starred not:important']) {
				logger.info("sync'ing priority inbox section", {query})
				const folder = getters.getFolder(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID)
				const list = folder.envelopeLists[normalizedEnvelopeListId(query)]
				if (list === undefined) {
					await dispatch('fetchEnvelopes', {
						accountId: UNIFIED_ACCOUNT_ID,
						folderId: UNIFIED_INBOX_ID,
						query,
					})
				}

				await dispatch('syncEnvelopes', {
					accountId: UNIFIED_ACCOUNT_ID,
					folderId: UNIFIED_INBOX_ID,
					query,
				})
			}
		} finally {
			showNewMessagesNotification(newMessages)
		}
	},
	toggleEnvelopeFlagged({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.flagged
		commit('flagEnvelope', {
			envelope,
			flag: 'flagged',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'flagged', !oldState).catch((e) => {
			console.error('could not toggle message flagged state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'flagged',
				value: oldState,
			})
		})
	},
	toggleEnvelopeImportant({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.important
		commit('flagEnvelope', {
			envelope,
			flag: 'important',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'important', !oldState).catch((e) => {
			console.error('could not toggle message important state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'important',
				value: oldState,
			})
		})
	},
	toggleEnvelopeSeen({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.seen
		commit('flagEnvelope', {
			envelope,
			flag: 'seen',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'seen', !oldState).catch((e) => {
			console.error('could not toggle message seen state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'seen',
				value: oldState,
			})
		})
	},
	toggleEnvelopeJunk({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.junk
		commit('flagEnvelope', {
			envelope,
			flag: 'junk',
			value: !oldState,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'junk', !oldState).catch((e) => {
			console.error('could not toggle message junk state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'junk',
				value: oldState,
			})
		})
	},
	markEnvelopeFavoriteOrUnfavorite({commit, getters}, {envelope, favFlag}) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.flagged
		commit('flagEnvelope', {
			envelope,
			flag: 'flagged',
			value: favFlag,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'flagged', favFlag).catch((e) => {
			console.error('could not favorite/unfavorite message ' + envelope.uid, e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'flagged',
				value: oldState,
			})
		})
	},
	markEnvelopeSeenOrUnseen({commit, getters}, {envelope, seenFlag}) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.unseen
		commit('flagEnvelope', {
			envelope,
			flag: 'unseen',
			value: seenFlag,
		})

		setEnvelopeFlag(envelope.accountId, envelope.folderId, envelope.id, 'unseen', seenFlag).catch((e) => {
			console.error('could not mark message ' + envelope.uid + ' seen/unseen', e)

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
		return fetchMessage(accountId, folderId, id).then((message) => {
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
	deleteMessage({getters, commit}, {accountId, folderId, id}) {
		commit('removeEnvelope', {accountId, folderId, id})

		return deleteMessage(accountId, folderId, id)
			.then(() => {
				const folder = getters.getFolder(accountId, folderId)
				if (!folder) {
					logger.error('could not find folder', {accountId, folderId})
					return
				}
				commit('removeMessage', {accountId, folder, id})
				console.log('message removed')
			})
			.catch((err) => {
				console.error('could not delete message', err)
				const envelope = getters.getEnvelope(accountId, folderId, id)
				if (envelope) {
					commit('addEnvelope', {accountId, folderId, envelope})
				} else {
					logger.error('could not find envelope', {accountId, folderId, id})
				}
				throw err
			})
	},
}
