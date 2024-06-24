/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateFilePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import '@nextcloud/dialogs/style.css'
import Vue from 'vue'

import AdminSettings from './components/settings/AdminSettings.vue'
import Nextcloud from './mixins/Nextcloud.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.mixin(Nextcloud)

const View = Vue.extend(AdminSettings)
new View({
	propsData: {
		provisioningSettings: loadState('mail', 'provisioning_settings') || [],
	},
}).$mount('#mail-admin-settings')
