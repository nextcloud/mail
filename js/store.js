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
	fetchMessage
} from './service/MessageService'

Vue.use(Vuex)

export const mutations = {
	addAccount (state, account) {
		account.folders = []
		Vue.set(state.accounts, account.id, account)
	},
	addFolder (state, {account, folder}) {
		let id = account.id + '-' + folder.id
		folder.envelopes = []
		Vue.set(state.folders, id, folder)
		account.folders.push(id)
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
	addMessage (state, {accountId, folderId, message}) {
		Vue.set(state.messages, accountId + '-' + folderId + '-' + message.id, message)
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
	fetchMessage ({commit}, {accountId, folderId, id}) {
		return fetchMessage(accountId, folderId, id).then(message => {
			commit('addMessage', {
				accountId,
				folderId,
				message
			})
			return message
		})
	}
}

export const getters = {
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getAccounts: (state) => () => {
		return state.accounts
	},
	getFolder: (state) => (accountId, folderId) => {
		return state.folders[accountId + '-' + folderId]
	},
	getFolders: (state) => (accountId) => {
		return state.accounts[accountId].folders.map(folderId => state.folders[folderId])
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
