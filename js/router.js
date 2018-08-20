import Vue from 'vue'
import Router from 'vue-router'
import Home from './views/Home.vue'

Vue.use(Router)

export default new Router({
	mode: 'history',
	base: OC.generateUrl('/apps/mail/'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			name: 'home',
			component: Home
		},
		{
			path: '/accounts/:accountId/folders/:folderId',
			name: 'folder',
			component: () => import(/* webpackChunkName: "about" */ './views/Setup.vue'),
		},
		{
			path: '/setup',
			name: 'setup',
			component: () => import(/* webpackChunkName: "about" */ './views/Setup.vue'),
		}
	]
})
