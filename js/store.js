import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
	state: {
		accounts: [
			{
				id: -1,
				folders: [
					{
						id: 'f1',
						name: 'Inbox',
					},
					{
						id: 'f2',
						name: 'Drafts'
					}
				]
			}
		]
	},
	mutations: {},
	actions: {}
})
