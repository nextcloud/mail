import {
	// updateSieveAccount,
	listFiltersets,
	// getScriptContent as getSieveScriptContent,
	// putScriptContent as putSieveScriptContent,
} from '../service/SieveService'

export default {
	namespaced: true,
	state() {
		return {
			ready: false,
			sieveAccount: {},
			activeFilterset: [],
			selectedFilterset: [],
			filtersetNames: [],
			filterRules: [],
			filtersetOrigin: [],
			filtersets: Object(),
			supportedSieveStructure: Object(),
		}
	},
	 mutations: {
		addFilterset(state, data) {
			state.sieveAccount[data.accountId] = {}
			state.sieveAccount[data.accountId].filtersets = {}
			state.sieveAccount[data.accountId].activeFilterset = data.data.activeScript
			state.sieveAccount[data.accountId].selectedFilterset = data.data.activeScript
			state.sieveAccount[data.accountId].filtersetNames = data.data.scripts
			state.sieveAccount[data.accountId].supportedSieveStructure = data.data.supportedSieveStructure
			state.sieveAccount[data.accountId].filtersets[state.sieveAccount[data.accountId].selectedFilterset] = data.data.scriptContent
			state.ready = true
		},
		extractFilterRules(state, data) {
			let i = 0
			state.sieveAccount[data.accountId].filterRules = []
			state.sieveAccount[data.accountId].filtersets[data.activeScript].forEach((rule, index) => {
				if (rule.type === 'header') {
					state.sieveAccount[data.accountId].filtersetOrigin = rule.scriptOrigin
				} else if (rule.type === 'rule') {
					rule.index = index
					state.sieveAccount[data.accountId].filterRules[i] = rule
					i++
				}
			})
		},
	},
	actions: {
		/* updateSieveAccount({commit}, account) {
			return updateSieveAccount(account)
				.then((data) => {
					console.info('UpdateSieveAccount returned')
					commit('setSieveStatus', {account, sieveEnabled: data.sieveEnabled})
					return data
				})
				.catch((err) => {
					console.info('UpdateSieveAccount errored')
					commit('setSieveStatus', {account, sieveEnabled: false})
					throw err
				})
		}, */
		listFiltersets({ commit }, accountId) {
			this.state.ready = false
			return listFiltersets(accountId)
				.then((data) => {
					console.info('sieve/listFiltersets returned')
					commit('addFilterset', { accountId, data })
					commit('extractFilterRules', { accountId, activeScript: data.activeScript })
					return data
				})
				.catch((err) => {
					console.info('sieve/listFiltersets errored')
					throw err
				})
		},
		/* getSieveScriptContent({commit}, {accountId, scriptName}) {
			return getSieveScriptContent(accountId, scriptName)
				.then((data) => {
					console.info('getSieveScriptContent returned')
					commit('addSieveScript', {scriptName, data})
					return data
				})
				.catch((err) => {
					console.info('getSieveScriptContent errored')
					throw err
				})
		},
		putSieveScriptContent({commit}, {accountId, scriptName, install, scriptContent}) {
			return putSieveScriptContent(accountId, scriptName, install, scriptContent)
				.then((data) => {
					console.info('putSieveScriptContent returned')
					return data
				})
				.catch((err) => {
					console.info('putSieveScriptContent errored')
					throw err
				})
		}, */
	},
	getters: {
		getActiveFilterset(state) {
			return accountId => state.sieveAccount[accountId].activeFilterset
		},
		getFiltersets(state) {
			return accountId => state.sieveAccount[accountId].filtersetNames
		},
		getSelectedFilterset(state) {
			return accountId => state.sieveAccount[accountId].selectedFilterset
		},
		getFiltersetOrigin(state) {
			return accountId => state.sieveAccount[accountId].filtersetOrigin
		},
		getFilterrules(state) {
			return accountId => state.sieveAccount[accountId].filterRules
		},
		isReady(state) {
			return state.ready
		},
		isActiveFilterset(state) {
			return accountId => state.sieveAccount[accountId].activeFilterset === state.sieveAccount[accountId].selectedFilterset
		},
	},
}
