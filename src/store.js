import _ from 'lodash'
import Vue from 'vue'
import Vuex from 'vuex'

import {
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts
} from './service/AccountService'
import {
	fetchAll as fetchAllFolders,
} from './service/FolderService'
import {
	fetchEnvelopes,
	syncEnvelopes,
	setEnvelopeFlag,
	fetchMessage,
	deleteMessage,
} from './service/MessageService'
import {
	showNewMessagesNotification
} from './service/NotificationService'

Vue.use(Vuex)

export const mutations = {
	addAccount (state, account) {
		account.folders = []
		account.collapsed = true
		Vue.set(state.accounts, account.id, account)
	},
	toggleAccountCollapsed (state, accountId) {
		state.accounts[accountId].collapsed = !state.accounts[accountId].collapsed
	},
	addFolder (state, {account, folder}) {
		let id = account.id + '-' + folder.id
		folder.envelopes = []
		Vue.set(state.folders, id, folder)
		account.folders.push(id)
	},
	updateFolderSyncToken (state, {folder, syncToken}) {
		folder.syncToken = syncToken
	},
	addEnvelope (state, {accountId, folder, envelope}) {
		let id = accountId + '-' + folder.id + '-' + envelope.id;
		Vue.set(state.envelopes, id, envelope)
		// TODO: prepend/append sort magic
		// TODO: reduce O(n) complexity
		if (folder.envelopes.indexOf(id) === -1) {
			// Prevent duplicates
			folder.envelopes.push(id)
		}
	},
	flagEnvelope (state, {accountId, folderId, id, flag, value}) {
		state.envelopes[accountId + '-' + folderId + '-' + id].flags[flag] = value
	},
	removeEnvelope (state, {accountId, folder, id}) {
		const envelopeId = accountId + '-' + folder.id + '-' + id
		const idx = folder.envelopes.indexOf(envelopeId)
		if (idx < 0) {
			console.warn('envelope does not exist', accountId, folder.id, id)
			return
		}
		folder.envelopes.splice(idx, 1)
		Vue.delete(folder.envelopes, envelopeId)
	},
	addMessage (state, {accountId, folderId, message}) {
		Vue.set(state.messages, accountId + '-' + folderId + '-' + message.id, message)
	},
	removeMessage (state, {accountId, folderId, id}) {
		Vue.delete(state.messages, accountId + '-' + folderId + '-' + id)
	}
}

export const actions = {
	fetchAccounts ({commit}) {
		return fetchAllAccounts().then(accounts => {
			accounts.forEach(account => commit('addAccount', account))
			return accounts
		})
	},
	fetchAccount ({commit}, id) {
		return fetchAccount(id).then(account => {
			commit('addAccount', account)
			return account
		})
	},
	fetchFolders ({commit, getters}, {accountId}) {
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
	fetchEnvelopes ({commit, getters}, {accountId, folderId}) {
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
	fetchNextEnvelopePage ({commit, getters}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)
		const lastEnvelopeId = folder.envelopes[folder.envelopes.length - 1]
		if (typeof lastEnvelopeId === 'undefined') {
			console.error('folder is empty', folder.envelopes)
			return Promise.reject(new Error('Local folder has no envelopes, cannot determine cursor'))
		}
		const lastEnvelope = getters.getEnvelopeById(lastEnvelopeId)
		if (typeof lastEnvelope === 'undefined') {
			return Promise.reject(new Error('Cannot find last envelope. Required for the folder cursor'))
		}

		console.debug('loading next envelope page, cursor=' + lastEnvelope.dateInt)

		return fetchEnvelopes(accountId, folderId, lastEnvelope.dateInt).then(envs => {
			console.debug('page loaded, size=' + envs.length)

			envs.forEach(envelope => commit('addEnvelope', {
				accountId,
				folder,
				envelope
			}))

			return envs
		})
	},
	syncEnvelopes ({commit, getters}, {accountId, folderId}) {
		const folder = getters.getFolder(accountId, folderId)
		const syncToken = folder.syncToken
		const uids = getters.getEnvelopes(accountId, folderId).map(env => env.id)

		return syncEnvelopes(accountId, folderId, syncToken, uids).then(syncData => {
			console.debug('got sync response:', syncData)
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
		console.debug('syncing all inboxes')
		return Promise.all(getters.getAccounts().map(account => {
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
				console.debug('synced all inboxes successfully')

				const newMessages = _.flatMapDeep(results).filter(_.negate(_.isUndefined))
				if (newMessages.length > 0) {
					showNewMessagesNotification(newMessages)
				}
			})
	},
	toggleEnvelopeFlagged ({commit, getters}, {accountId, folderId, id}) {
		// Change immediately and switch back on error
		const oldState = getters.getEnvelope(accountId, folderId, id).flags.flagged
		commit('flagEnvelope', {
			accountId,
			folderId,
			id,
			flag: 'flagged',
			value: !oldState
		})

		setEnvelopeFlag(accountId, folderId, id, 'flagged', !oldState).catch(e => {
			console.error('could not toggle message flagged state', e)

			// Revert change
			commit('flagEnvelope', {
				accountId,
				folderId,
				id,
				flag: 'flagged',
				value: oldState
			})
		})
	},
	toggleEnvelopeSeen ({commit, getters}, {accountId, folderId, id}) {
		// Change immediately and switch back on error
		const oldState = getters.getEnvelope(accountId, folderId, id).flags.unseen
		commit('flagEnvelope', {
			accountId,
			folderId,
			id,
			flag: 'unseen',
			value: !oldState
		})

		setEnvelopeFlag(accountId, folderId, id, 'unseen', !oldState).catch(e => {
			console.error('could not toggle message unseen state', e)

			// Revert change
			commit('flagEnvelope', {
				accountId,
				folderId,
				id,
				flag: 'unseen',
				value: oldState
			})
		})
	},
	fetchMessage ({commit}, {accountId, folderId, id}) {
		return fetchMessage(accountId, folderId, id).then(message => {
			commit('addMessage', {
				accountId,
				folderId,
				message
			})
			return message
		})
	},
	deleteMessage ({getters, commit}, {accountId, folderId, id}) {
		const folder = getters.getFolder(accountId, folderId)
		const envelope = getters.getEnvelope(accountId, folderId, id)
		commit('removeEnvelope', {accountId, folder, id})

		return deleteMessage(accountId, folderId, id)
			.then(() => {
				console.log('message removed')
				commit('removeMessage', {
					accountId,
					folder,
					id,
				})
			})
			.catch(err => {
				console.error('could not delete message', err)
				commit('addEnvelope', {accountId, folderId, envelope})
				throw err
			})
	}
}

export const getters = {
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getAccounts: (state) => () => {
		return Object.keys(state.accounts).map(id => state.accounts[id])
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
		accounts: {},
		folders: {},
		envelopes: {},
		messages: {},
	},
	getters,
	mutations,
	actions
})
