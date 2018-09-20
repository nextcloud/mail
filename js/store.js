import Vue from 'vue'
import Vuex from 'vuex'

import {
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts
} from './service/AccountService'
import {fetchEnvelopes} from './service/MessageService'

Vue.use(Vuex)

export const mutations = {
	addAccount (state, account) {
		Vue.set(state.accounts, account.id, account)
	},
	addEnvelope (state, {accountId, folderId, envelope}) {
		// TODO: append/add to folder envelopes list
		Vue.set(state.envelopes, accountId + '-' + folderId + '-' + envelope.id, envelope)
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
	fetchEnvelopes ({commit, getters}, {accountId, folderId}) {
		return fetchEnvelopes(accountId, folderId).then(envs => {
			envs.forEach(envelope => commit('addEnvelope', {
				accountId,
				folderId,
				envelope
			}))
			return envs
		})
	}
}

export const getters = {
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getFolder: (state) => (accountId, folderId) => {
		return state.folders[accountId + '-' + folderId]
	},
	getFolders: (state) => (accountId) => {
		return state.accounts[accountId].folders.map(folderId => state.folders[folderId])
	},
	getEnvelopes: (state, getters) => (accountId, folderId) => {
		return getters.getFolder(accountId, folderId).envelopes.map(msgId => state.envelopes[msgId])
	}
}

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',
	state: {
		accounts: {},
		folders: {
			'1-SU5CT1g=': {
				id: 'SU5CT1g=',
				name: 'Inbox',
				specialUse: 'inbox',
				unread: 2,
				envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
			},
		},
		envelopes: {},
		messages: {},
	},
	getters,
	mutations,
	actions
})
