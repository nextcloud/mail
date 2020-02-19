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
	concat,
	curry,
	filter,
	flatten,
	identity,
	map,
	mergeDeepRight,
	last,
	path,
	pipe,
	prop,
	propEq,
	reduce,
	slice,
	tap,
	uniq,
} from 'ramda'

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
import {
	deleteMessage,
	fetchEnvelope,
	fetchEnvelopes,
	fetchMessage,
	setEnvelopeFlag,
	syncEnvelopes,
} from '../service/MessageService'
import logger from '../logger'
import {showNewMessagesNotification} from '../service/NotificationService'
import {parseUid} from '../util/EnvelopeUidParser'

const findIndividualFolders = curry((getFolders, specialRole) =>
	pipe(
		filter(complement(prop('isUnified'))),
		map(prop('accountId')),
		map(getFolders),
		flatten,
		filter(propEq('specialRole', specialRole))
	)
)
const combineEnvelopeLists = pipe(flatten, orderBy(prop('dateInt'), 'desc'), slice(0, 19))

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
	fetchEnvelope({commit, getters}, uid) {
		const {accountId, folderId, id} = parseUid(uid)

		const cached = getters.getEnvelope(accountId, folderId, id)
		if (cached) {
			return cached
		}

		return fetchEnvelope(accountId, folderId, id).then(envelope => {
			// Only commit if not undefined (not found)
			if (envelope) {
				commit('addEnvelope', {
					accountId,
					folderId,
					envelope,
				})
			}

			return envelope
		})
	},
	fetchEnvelopes({state, commit, getters, dispatch}, {accountId, folderId, query}) {
		const folder = getters.getFolder(accountId, folderId)
		if (folder.isUnified) {
			// Fetch and combine envelopes of all individual folders
			//
			// The last envelope is excluded to efficiently build the next unified
			// pages (fetch only individual pages that do not have sufficient local
			// data)
			//
			// TODO: handle short/ending streams and show their last element as well
			const fetchIndividualLists = pipe(
				map(f =>
					dispatch('fetchEnvelopes', {
						accountId: f.accountId,
						folderId: f.id,
						query,
					})
				),
				Promise.all.bind(Promise),
				andThen(map(slice(0, 19)))
			)

			return pipe(
				findIndividualFolders(getters.getFolders, folder.specialRole),
				fetchIndividualLists,
				andThen(combineEnvelopeLists)
			)(getters.accounts)
		}

		return pipe(
			fetchEnvelopes,
			andThen(
				tap(
					map(envelope =>
						commit('addEnvelope', {
							accountId,
							folderId,
							envelope,
						})
					)
				)
			)
		)(accountId, folderId, query)
	},
	fetchNextUnifiedEnvelopePage({state, commit, getters, dispatch}, {accountId, folderId, envelopes, query}) {
		const folder = getters.getFolder(accountId, folderId)
		const findIndividual = findIndividualFolders(getters.getFolders, folder.specialRole)

		const fetchAndCombineAll = pipe(
			findIndividual,
			map(f =>
				dispatch('fetchNextEnvelopePage', {
					accountId: f.accountId,
					folderId: f.id,
					envelopes: filter(propEq('folderId', f.id), envelopes),
					query,
				})
			),
			Promise.all.bind(Promise),
			andThen(tap(console.info.bind(this))),
			andThen(combineEnvelopeLists),
			andThen(tap(console.info.bind(this)))
		)

		return fetchAndCombineAll(getters.accounts)
	},
	fetchNextEnvelopePage({commit, getters, dispatch}, {accountId, folderId, envelopes, query}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			return dispatch('fetchNextUnifiedEnvelopePage', {
				accountId,
				folderId,
				envelopes,
				query,
			})
		}

		const cursorElement = last(envelopes)
		if (typeof cursorElement === 'undefined') {
			console.warn('No cursor given -> fetching first page')
			return dispatch('fetchEnvelopes', {
				accountId,
				folderId,
				query,
			})
		}

		return pipe(
			fetchEnvelopes,
			andThen(
				tap(
					map(envelope =>
						commit('addEnvelope', {
							accountId,
							folderId,
							envelope,
						})
					)
				)
			)
		)(accountId, folderId, query, cursorElement.dateInt)
	},
	syncEnvelopes({commit, getters, dispatch}, {accountId, folderId, uids, init = false}) {
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
										init,
									})
								)
						)
					)
			)
		}

		return syncEnvelopes(accountId, folderId, uids, init).then(syncData => {
			syncData.newMessages.forEach(envelope => {
				commit('addEnvelope', {
					accountId,
					folderId,
					envelope,
				})
			})
			syncData.changedMessages.forEach(envelope => {
				commit('addEnvelope', {
					accountId,
					folderId,
					envelope,
				})
			})

			return {
				...syncData,
				accountId,
				folderId,
				uids,
			}
		})
	},
	syncInboxes({getters, dispatch}, syncContext) {
		const findInboxes = findIndividualFolders(getters.getFolders, 'inbox')
		const getUids = curry((context, folder) => {
			const uids = path([folder.accountId, folder.id, 'uids'], context)
			if (uids && uids.length) {
				return Promise.resolve({
					folder,
					uids,
				})
			}
			return dispatch('fetchEnvelopes', {
				accountId: folder.accountId,
				folderId: folder.id,
			}).then(envelopes => {
				return {
					folder,
					uids: map(prop('id'), envelopes),
				}
			})
		})

		const syncAll = pipe(
			findInboxes,
			map(getUids(syncContext)),
			Promise.all.bind(Promise),
			andThen(map(({folder, uids}) => dispatch('syncEnvelopes', {
				accountId: folder.accountId,
				folderId: folder.id,
				uids,
			}))),
			andThen(Promise.all.bind(Promise)),
			andThen(map(d => ({
				[d.accountId]: {
					[d.folderId]: {
						newMessages: d.newMessages,
						uids: uniq(concat(d.uids, d.newMessages)), // TODO: remove vanished?
					}
				}})
			)),
			andThen(reduce(mergeDeepRight, {})),
			andThen(tap(console.info.bind(this))),
		)

		return syncAll(getters.accounts)

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
					folderId: envelope.folderId,
					envelope,
				})
				throw err
			})
	},
}
