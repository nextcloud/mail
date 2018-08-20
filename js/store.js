import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
	state: {
		accounts: [
			/*{
				id: -1,
				name: 'All inboxes',
				specialUse: 'inbox',
				unread: 2
			},*/
			{
				id: 1,
				name: 'email1@domain.com',
				bullet: '#ee2629',
				folders: [
					{
						id: 'folder1',
						name: 'Inbox',
						specialUse: 'inbox',
						unread: 2
					},
					{
						id: 'folder2',
						name: 'Favorites',
						specialUse: 'flagged',
						unread: 2
					},
					{
						id: 'folder3',
						name: 'Drafts',
						specialUse: 'drafts',
						unread: 1
					},
					{
						id: 'folder4',
						name: 'Sent',
						specialUse: 'sent',
						unread: 2000
					},
					{
						id: 'folder5',
						name: 'Show all',
					}
				]
			},
			{
				id: 2,
				name: 'email2@domain.com',
				bullet: '#81ee53',
				folders: [
					{
						id: 'folder2',
						name: 'Inbox',
						specialUse: 'inbox',
						utils: {
							counter: 0
						}
					}
				]
			}
		]
	},
	mutations: {},
	actions: {}
})
