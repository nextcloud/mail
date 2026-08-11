/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { registerDavProperty } from '@nextcloud/files'
import { generateFilePath } from '@nextcloud/router'
import { createPinia, PiniaVuePlugin } from 'pinia'
import vToolTip from 'v-tooltip'
import Vue from 'vue'
import VueShortKey from 'vue-shortkey'
import App from './App.vue'
import Nextcloud from './mixins/Nextcloud.js'
import router from './router.js'

import '@nextcloud/dialogs/style.css'
import './directives/drag-and-drop/styles/drag-and-drop.scss'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

Vue.mixin(Nextcloud)

Vue.use(VueShortKey, { prevent: ['input', 'div', 'textarea'] })
Vue.use(vToolTip)

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })

export default new Vue({
	el: '#content',
	name: 'Mail',
	router,
	pinia,
	render: (h) => h(App),
})
