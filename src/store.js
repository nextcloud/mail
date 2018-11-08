import _ from 'lodash'
import Vue from 'vue'
import Vuex from 'vuex'
import {translate as t} from 'nextcloud-server/dist/l10n'

import {
	create as createAccount,
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts
} from './service/AccountService'
import {fetchAll as fetchAllFolders,} from './service/FolderService'
import {
	deleteMessage,
	fetchEnvelopes,
	fetchMessage,
	setEnvelopeFlag,
	syncEnvelopes,
} from './service/MessageService'
import {showNewMessagesNotification} from './service/NotificationService'
import {parseUid} from './util/EnvelopeUidParser'

Vue.use(Vuex)

const UNIFIED_ACCOUNT_ID = 0
const UNIFIED_INBOX_ID = 'inbox'
const UNIFIED_INBOX_UID = UNIFIED_ACCOUNT_ID + '-' + UNIFIED_INBOX_ID

export const mutations = {
	addAccount (state, account) {
		account.folders = []
		account.collapsed = true
		Vue.set(state.accounts, account.id, account)
		Vue.set(
			state,
			'accountList',
			_.sortBy(state.accountList.concat([account.id]))
		)
	},
	toggleAccountCollapsed (state, accountId) {
		state.accounts[accountId].collapsed = !state.accounts[accountId].collapsed
	},
	addFolder (state, {account, folder}) {
		let id = account.id + '-' + folder.id
		folder.accountId = account.id
		folder.envelopes = []
		Vue.set(state.folders, id, folder)
		account.folders.push(id)
	},
	updateFolderSyncToken (state, {folder, syncToken}) {
		folder.syncToken = syncToken
	},
	addEnvelope (state, {accountId, folder, envelope}) {
		const uid = accountId + '-' + folder.id + '-' + envelope.id
		const insert = folder => {
			Vue.set(
				folder,
				'envelopes',
				_.sortedUniq(
					_.orderBy(
						folder.envelopes.concat([uid]),
						id => state.envelopes[id].dateInt,
						'desc'
					)
				)
			)
		}

		envelope.accountId = accountId
		envelope.folderId = folder.id
		envelope.uid = uid
		Vue.set(state.envelopes, uid, envelope)
		insert(folder)
	},
	addUnifiedEnvelopes (state, {folder, uids}) {
		Vue.set(folder, 'envelopes', uids)
	},
	flagEnvelope (state, {envelope, flag, value}) {
		envelope.flags[flag] = value
	},
	removeEnvelope (state, {accountId, folder, id}) {
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
	addMessage (state, {accountId, folderId, message}) {
		const uid = accountId + '-' + folderId + '-' + message.id
		message.accountId = accountId
		message.folderId = folderId
		message.uid = uid
		Vue.set(state.messages, uid, message)
	},
	removeMessage (state, {accountId, folderId, id}) {
		Vue.delete(state.messages, accountId + '-' + folderId + '-' + id)
	}
}

export const actions = {
	fetchAccounts ({commit, getters}) {
		return fetchAllAccounts().then(accounts => {
			accounts.forEach(account => commit('addAccount', account))
			return getters.getAccounts()
		})
	},
	fetchAccount ({commit}, id) {
		return fetchAccount(id).then(account => {
			commit('addAccount', account)
			return account
		})
	},
	createAccount ({commit}, config) {
		return createAccount(config)
			.then(account => {
				console.debug('account created', account)
				commit('addAccount', account)
				return account
			})
	},
	fetchFolders ({commit, getters}, {accountId}) {
		if (getters.getAccount(accountId).isUnified) {
			return Promise.resolve(getters.getFolders(accountId))
		}

		return fetchAllFolders(accountId).then(folders => {
			let account = getters.getAccount(accountId)

			folders.forEach(folder => {
				commit('addFolder', {
					account,
					folder
				})
			})
			return folders
		})
	},
	fetchEnvelopes ({state, commit, getters, dispatch}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)
		if (folder.isUnified) {
			// Fetch and combine envelopes of all individual folders
			//
			// The last envelope is excluded to efficiently build the next unified
			// pages (fetch only individual pages that do not have sufficient local
			// data)
			//
			// TODO: handle short/ending streams and show their last element as well
			return Promise.all(
				getters.getAccounts()
					.filter(account => !account.isUnified)
					.map(account => Promise.all(
						getters.getFolders(account.id)
							.filter(f => f.specialRole === folder.specialRole)
							.map(folder => dispatch('fetchEnvelopes', {
								accountId: account.id,
								folderId: folder.id,
							})))
					)
			)
				.then(res => res.map(envs => envs.slice(0, 19)))
				.then(res => _.flattenDepth(res, 2))
				.then(envs => _.orderBy(
					envs,
					env => env.dateInt,
					'desc'
				))
				.then(envs => _.slice(envs, 0, 19)) // 19 to handle non-overlapping streams
				.then(envelopes => {
					commit('addUnifiedEnvelopes', {
						folder,
						uids: envelopes.map(e => e.uid),
					})
					return envelopes
				})
		}

		return fetchEnvelopes(accountId, folderId).then(envs => {
			let folder = getters.getFolder(accountId, folderId)

			envs.forEach(envelope => commit('addEnvelope', {
				accountId,
				folder,
				envelope
			}))
			return envs
		})
	},
	fetchNextUnifiedEnvelopePage ({state, commit, getters, dispatch}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)

		// We only care about folders of the same type/role
		const individualFolders = _.flatten(
			getters.getAccounts()
				.filter(a => !a.isUnified)
				.map(a => getters.getFolders(a.id)
					.filter(f => f.specialRole === folder.specialRole))
		)
		// Build a sorted list of all currently known envelopes (except last elem)
		const knownEnvelopes = _.orderBy(
			_.flatten(
				individualFolders.map(f => f.envelopes.slice(0, f.envelopes.length - 1))
			),
			id => state.envelopes[id].dateInt,
			'desc'
		)
		// The index of the last element in the current unified mailbox determines
		// the new offset
		const tailId = _.last(folder.envelopes)
		const tailIdx = knownEnvelopes.indexOf(tailId)
		if (tailIdx === -1) {
			return Promise.reject(new Error('current envelopes do not contain unified mailbox tail. this should not have happened'))
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
			.filter(f => !_.isEmpty(f.envelopes))
			.filter(f => {
				const lastShown = f.envelopes[f.envelopes.length - 2]
				return nextCandidates.length <= 18 || nextCandidates.indexOf(lastShown) !== -1
			})

		if (_.isEmpty(needFetch)) {
			commit('addUnifiedEnvelopes', {
				folder,
				uids: _.sortedUniq(
					_.orderBy(
						folder.envelopes.concat(nextCandidates),
						id => state.envelopes[id].dateInt,
						'desc'
					)
				),
			})
		} else {
			return Promise.all(needFetch
				.map(f => dispatch('fetchNextEnvelopePage', {
					accountId: f.accountId,
					folderId: f.id
				})))
				.then(() => {
					return dispatch('fetchNextUnifiedEnvelopePage', {
						accountId,
						folderId
					})
				})
		}
	},
	fetchNextEnvelopePage ({commit, getters, dispatch}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			return dispatch('fetchNextUnifiedEnvelopePage', {accountId, folderId})
		}

		const lastEnvelopeId = folder.envelopes[folder.envelopes.length - 1]
		if (typeof lastEnvelopeId === 'undefined') {
			console.error('folder is empty', folder.envelopes)
			return Promise.reject(new Error('Local folder has no envelopes, cannot determine cursor'))
		}
		const lastEnvelope = getters.getEnvelopeById(lastEnvelopeId)
		if (typeof lastEnvelope === 'undefined') {
			return Promise.reject(new Error('Cannot find last envelope. Required for the folder cursor'))
		}

		return fetchEnvelopes(accountId, folderId, lastEnvelope.dateInt).then(envs => {
			envs.forEach(envelope => commit('addEnvelope', {
				accountId,
				folder,
				envelope
			}))

			return envs
		})
	},
	syncEnvelopes ({commit, getters, dispatch}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)

		if (folder.isUnified) {
			return Promise.all(
				getters.getAccounts()
					.filter(account => !account.isUnified)
					.map(account => Promise.all(
						getters.getFolders(account.id)
							.filter(f => f.specialRole === folder.specialRole)
							.map(folder => dispatch('syncEnvelopes', {
								accountId: account.id,
								folderId: folder.id,
							})))
					)
			)
		}

		const syncToken = folder.syncToken
		const uids = getters.getEnvelopes(accountId, folderId).map(env => env.id)

		return syncEnvelopes(accountId, folderId, syncToken, uids).then(syncData => {
			syncData.newMessages.concat(syncData.changedMessages).forEach(envelope => {
				commit('addEnvelope', {
					accountId,
					folder,
					envelope
				})
			})
			syncData.vanishedMessages.forEach(envelope => {
				commit('removeEnvelope', {
					accountId,
					folder,
					id: envelope.id
				})
			})
			commit('updateFolderSyncToken', {
				folder,
				syncToken: syncData.token
			})

			return syncData.newMessages
		})
	},
	syncInboxes ({getters, dispatch}) {
		return Promise.all(getters.getAccounts()
			.filter(a => !a.isUnified)
			.map(account => {
				return Promise.all(getters.getFolders(account.id).map(folder => {
					if (folder.specialRole !== 'inbox') {
						return
					}

					return dispatch('syncEnvelopes', {
						accountId: account.id,
						folderId: folder.id,
					})
				}))
			}))
			.then(results => {
				const newMessages = _.flatMapDeep(results).filter(_.negate(_.isUndefined))
				if (newMessages.length > 0) {
					showNewMessagesNotification(newMessages)
				}
			})
	},
	toggleEnvelopeFlagged ({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.flagged
		commit('flagEnvelope', {
			envelope,
			flag: 'flagged',
			value: !oldState
		})

		setEnvelopeFlag(
			envelope.accountId,
			envelope.folderId,
			envelope.id,
			'flagged',
			!oldState
		).catch(e => {
			console.error('could not toggle message flagged state', e)

			// Revert change
			commit('flagEnvelope', {
				envelope,
				flag: 'flagged',
				value: oldState
			})
		})
	},
	toggleEnvelopeSeen ({commit, getters}, envelope) {
		// Change immediately and switch back on error
		const oldState = envelope.flags.unseen
		commit('flagEnvelope', {
			envelope,
			flag: 'unseen',
			value: !oldState
		})

		setEnvelopeFlag(
			envelope.accountId,
			envelope.folderId,
			envelope.id,
			'unseen',
			!oldState
		)
			.catch(e => {
				console.error('could not toggle message unseen state', e)

				// Revert change
				commit('flagEnvelope', {
					envelope,
					flag: 'unseen',
					value: oldState
				})
			})
	},
	fetchMessage ({commit}, uid) {
		const {accountId, folderId, id} = parseUid(uid)
		return fetchMessage(accountId, folderId, id).then(message => {
			commit('addMessage', {
				accountId,
				folderId,
				message
			})
			return message
		})
	},
	deleteMessage ({getters, commit}, envelope) {
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
	}
}

export const getters = {
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getAccounts: (state) => () => {
		return state.accountList.map(id => state.accounts[id])
	},
	getFolder: (state) => (accountId, folderId) => {
		return state.folders[accountId + '-' + folderId]
	},
	getFolders: (state) => (accountId) => {
		return state.accounts[accountId].folders.map(folderId => state.folders[folderId])
	},
	getEnvelope: (state) => (accountId, folderId, id) => {
		return state.envelopes[accountId + '-' + folderId + '-' + id]
	},
	getEnvelopeById: (state) => (id) => {
		return state.envelopes[id]
	},
	getEnvelopes: (state, getters) => (accountId, folderId) => {
		return getters.getFolder(accountId, folderId).envelopes.map(msgId => state.envelopes[msgId])
	},
	getMessage: (state) => (accountId, folderId, id) => {
		return state.messages[accountId + '-' + folderId + '-' + id]
	},
}

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',
	state: {
		accounts: {
			[UNIFIED_ACCOUNT_ID]: {
				id: UNIFIED_ACCOUNT_ID,
				isUnified: true,
				folders: [UNIFIED_INBOX_UID],
				collapsed: false,
				emailAddress: '',
				name: ''
			},
		},
		accountList: [
			UNIFIED_ACCOUNT_ID,
		],
		folders: {
			[UNIFIED_INBOX_UID]: {
				id: UNIFIED_INBOX_ID,
				accountId: 0,
				isUnified: true,
				specialRole: 'inbox',
				name: t('mail', 'All inboxes'), // TODO,
				unread: 0,
				envelopes: [],
			}
		},
		envelopes: {},
		messages: {},
	},
	getters,
	mutations,
	actions
})
