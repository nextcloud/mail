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
	addMessage (state, {accountId, folderId, message}) {
		Vue.set(state.messages, accountId + '-' + folderId + '-' + message.id, message)
	}
}

export const actions = {
	fetchAccounts ({commit}) {
		return fetchAllAccounts().then(accounts => accounts.map(account => commit('addAccount', account)))
	},
	fetchAccount ({commit}, id) {
		return fetchAccount(id).then(account => commit('addAccount', account))
	},
	fetchMessages ({commit}, {accountId, folderId}) {
		return fetchEnvelopes(accountId, folderId).then(msgs => msgs.map(message => commit('addMessage', {
			accountId,
			folderId,
			message
		})))
	}
}

export const getters = {
	getAccount: (state) => (id) => {
		return state.accounts[id]
	},
	getFolder: (state) => (accountId, folderId) => {
		return state.folders[accountId + '-' + folderId]
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
				id: 'folder1',
				name: 'Inbox',
				specialUse: 'inbox',
				unread: 2,
				envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
			},
			2: {
				id: 'folder2',
				name: 'Favorites',
				specialUse: 'flagged',
				unread: 2
			},
			3: {
				id: 'folder3',
				name: 'Drafts',
				specialUse: 'drafts',
				unread: 1
			},
			4: {
				id: 'folder4',
				name: 'Sent',
				specialUse: 'sent',
				unread: 2000
			},
			5: {
				id: 'folder5',
				name: 'Show all',
			}
		},
		envelopes: {
			'1-SU5CT1g=-1': {
				id: '1',
				from: 'Sender 1',
				subject: 'Message 1',
				envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
			},
			'1-SU5CT1g=-2': {
				id: '2',
				from: 'Sender 2',
				subject: 'Message 2',
				envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
			},
			'1-SU5CT1g=-3': {
				id: '3',
				from: 'Sender 3',
				subject: 'Message 3',
				envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
			}
		},
		messages: [],
	},
	getters,
	mutations,
	actions
})
