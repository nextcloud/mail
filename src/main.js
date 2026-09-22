/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { registerDavProperty } from '@nextcloud/files/dav'
import { generateFilePath } from '@nextcloud/router'
import { n, t } from '@nextcloud/l10n'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import VueShortKey from 'vue3-shortkey'
import App from './App.vue'
import router from './router.js'

import '@nextcloud/dialogs/style.css'
import './directives/drag-and-drop/styles/drag-and-drop.scss'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

const pinia = createPinia()

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })

const app = createApp(App)
app.use(router)
app.use(pinia)
app.use(VueShortKey, { prevent: ['input', 'div', 'textarea'] })
app.config.globalProperties.t = t
app.config.globalProperties.n = n
app.mount('#content')
