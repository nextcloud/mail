/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { registerDavProperty } from '@nextcloud/files'
import { generateFilePath } from '@nextcloud/router'
import { createPinia, PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import VueShortKey from 'vue-shortkey'
import App from './App.vue'
import Nextcloud from './mixins/Nextcloud.js'
import router from './router.js'
import { installJumpChords } from './shortcuts.js'

import '@nextcloud/dialogs/style.css'
import './directives/drag-and-drop/styles/drag-and-drop.scss'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

Vue.mixin(Nextcloud)

// Chord handling listens on window so it runs ahead of vue-shortkey's
// document listeners regardless of import order.
installJumpChords()

// 'div' used to be in this list to stop shortcuts firing while typing in the
// CKEditor composer, which is a contenteditable div. But vue-shortkey tests
// document.activeElement against these selectors, and Nextcloud focuses plain
// divs (#app-content, the envelope list), so every shortcut was suppressed
// almost all of the time. Target the editable elements themselves instead.
Vue.use(VueShortKey, {
	prevent: ['input', 'textarea', '[contenteditable]:not([contenteditable="false"])'],
})

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })

export default new Vue({
	el: '#content',
	name: 'Mail',
	router,
	pinia,
	render: (h) => h(App),
})
