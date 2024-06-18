/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line import/no-unresolved, n/no-missing-import
import 'vite/modulepreload-polyfill'

import Vue from 'vue'

import OauthDone from './views/OauthDone.vue'
import Nextcloud from './mixins/Nextcloud.js'

Vue.mixin(Nextcloud)

const View = Vue.extend(OauthDone)
new View({}).$mount('#mail-oauth-done')

if (window.opener) {
	window.opener.postMessage('DONE')
	window.close()
}
