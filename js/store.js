import Vue from 'vue'
import Vuex from 'vuex'

import {
	fetch as fetchAccount,
	fetchAll as fetchAllAccounts
} from './service/AccountService'

Vue.use(Vuex)

export const mutations = {
	addAccount (state, account) {
		Vue.set(state.accounts, account.id, account)
	}
}

export const actions = {
	fetchAccounts ({commit}) {
		return fetchAllAccounts().then(accounts => accounts.map(account => commit('addAccount', account)))
	},
	fetchAccount ({commit}, id) {
		return fetchAccount(id).then(account => commit('addAccount', account))
	}
}

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',
	state: {
		accounts: {},
		folders: {
			1: {
				id: 'folder1',
				name: 'Inbox',
				specialUse: 'inbox',
				unread: 2
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
		}
	},
	getters: {
		currentFolder (state) {
			return []
		}
	},
	mutations,
	actions
})
