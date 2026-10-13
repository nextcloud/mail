/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { n, t } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import OauthDone from './views/OauthDone.vue'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

const app = createApp(OauthDone)
app.config.globalProperties.t = t
app.config.globalProperties.n = n
app.mount('#mail-oauth-done')

if (window.opener) {
	window.opener.postMessage('DONE')
}
