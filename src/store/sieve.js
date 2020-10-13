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
			activeFilterset: '',
			selectedFilterset: '',
			filtersetNames: [],
			filterRules: [],
			filtersetOrigin: '',
			filtersets: Object(),
			supportedSieveStructure: Object(),
		}
	},
	 mutations: {
		addFilterset(state, data) {
			state.activeFilterset = data.activeScript
			state.selectedFilterset = data.activeScript
			state.filtersetNames = data.scripts
			state.supportedSieveStructure = data.supportedSieveStructure
			state.filtersets[state.selectedFilterset] = data.scriptContent
		},
		extractFilterRules(state, scriptName) {
			let i = 0
			state.filterRules = []
			state.filtersets[scriptName].forEach((rule, index) => {
				if (rule.type === 'header') {
					state.filtersetOrigin = rule.scriptOrigin
				} else if (rule.type === 'rule') {
					rule.index = index
					state.filterRules[i] = rule
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
			return listFiltersets(accountId)
				.then((data) => {
					console.info('sieve/listFiltersets returned')
					commit('addFilterset', data)
					commit('extractFilterRules', data.activeScript)
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
			return state.activeFilterset
		},
		getFiltersets(state) {
			return state.filtersetNames
		},
		getSelectedFilterset(state) {
			return state.selectedFilterset
		},
		getFiltersetOrigin(state) {
			return state.filtersetOrigin
		},
		isActiveFilterset(state) {
			return state.activeFilterset === state.selectedFilterset
		},
	},
}
