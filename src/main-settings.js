/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { n, t } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import AdminSettings from './components/settings/AdminSettings.vue'

import '@nextcloud/dialogs/style.css'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

const app = createApp(AdminSettings, {
	provisioningSettings: loadState('mail', 'provisioning_settings') || [],
})
app.config.globalProperties.t = t
app.config.globalProperties.n = n
app.mount('#mail-admin-settings')
