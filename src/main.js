/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { registerDavProperty } from '@nextcloud/files'
import { generateFilePath } from '@nextcloud/router'
import { createPinia } from 'pinia'
import FloatingVue from 'floating-vue'
import { createApp } from 'vue'
import VueShortKey from 'vue3-shortkey'
import App from './App.vue'
import Nextcloud from './mixins/Nextcloud.js'
import router from './router.js'

import '@nextcloud/dialogs/style.css'
import './directives/drag-and-drop/styles/drag-and-drop.scss'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

const pinia = createPinia()
const app = createApp(App)

app.mixin(Nextcloud)
app.use(pinia)
app.use(router)
app.use(VueShortKey, { prevent: ['input', 'div', 'textarea'] })
app.use(FloatingVue)

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })

app.mount('#content')
