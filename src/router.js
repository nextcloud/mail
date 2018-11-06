import Vue from 'vue'
import Router from 'vue-router'
import {generateUrl} from 'nextcloud-server/dist/router'

import AccountSettings from './views/AccountSettings'
import Home from './views/Home'
import Setup from './views/Setup'

Vue.use(Router)

export default new Router({
	base: generateUrl('/apps/mail/'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			name: 'home',
			component: Home
		},
		{
			path: '/mailto',
			name: 'mailto',
			component: Home
		},
		{
			path: '/accounts/:accountId/folders/:folderId',
			name: 'folder',
			component: Home
		},
		{
			path: '/accounts/:accountId/folders/:folderId/message/:messageUid',
			name: 'message',
			component: Home
		},
		{
			path: '/accounts/:accountId/settings',
			name: 'accountSettings',
			component: AccountSettings
		},
		{
			path: '/setup',
			name: 'setup',
			component: Setup
		}
	]
});
