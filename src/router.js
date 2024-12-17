/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'

const Home = () => import('./views/Home.vue')
const Setup = () => import('./views/Setup.vue')

Vue.use(Router)

export default new Router({
	mode: 'history',
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
			path: '/box/:filter?/:mailboxId',
			name: 'mailbox',
			component: Home,
		},
		{
			path: '/box/:filter?/:mailboxId/thread/:threadId/:draftId?',
			name: 'message',
			component: Home,
		},
		{
			path: '/outbox',
			name: 'outbox',
			component: Home,
		},
		{
			path: '/setup',
			name: 'setup',
			component: Setup,
		},
	],
})
