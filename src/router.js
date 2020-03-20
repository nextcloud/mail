import Vue from 'vue'
import Router from 'vue-router'
import {generateUrl} from '@nextcloud/router'

const AccountSettings = () => import('./views/AccountSettings')
const Home = () => import('./views/Home')
const KeyboardShortcuts = () => import('./views/KeyboardShortcuts')
const Setup = () => import('./views/Setup')

Vue.use(Router)

export default new Router({
	base: generateUrl('/apps/mail/'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			name: 'home',
			component: Home,
		},
		{
			path: '/mailto',
			name: 'mailto',
			component: Home,
		},
		{
			path: '/accounts/:accountId/folders/:filter?/:folderId',
			name: 'folder',
			component: Home,
		},
		{
			path: '/accounts/:accountId/folders/:filter?/:folderId/message/:messageUid/:draftUid?',
			name: 'message',
			component: Home,
		},
		{
			path: '/accounts/:accountId/settings',
			name: 'accountSettings',
			component: AccountSettings,
		},
		{
			path: '/keyboard-shortcuts',
			name: 'keyboardShortcuts',
			component: KeyboardShortcuts,
		},
		{
			path: '/setup',
			name: 'setup',
			component: Setup,
		},
	],
})
