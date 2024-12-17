/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'
import Vue from 'vue'

import OauthDone from './views/OauthDone.vue'
import Nextcloud from './mixins/Nextcloud.js'

__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.mixin(Nextcloud)

const View = Vue.extend(OauthDone)
new View({}).$mount('#mail-oauth-done')

if (window.opener) {
	window.opener.postMessage('DONE')
	window.close()
}
