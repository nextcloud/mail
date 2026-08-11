/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { generateFilePath } from '@nextcloud/router'
import Vue from 'vue'
import AdminSettings from './components/settings/AdminSettings.vue'
import Nextcloud from './mixins/Nextcloud.js'

import '@nextcloud/dialogs/style.css'

__webpack_nonce__ = btoa(getRequestToken())

__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.mixin(Nextcloud)

const View = Vue.extend(AdminSettings)
new View({
	propsData: {
		provisioningSettings: loadState('mail', 'provisioning_settings') || [],
	},
}).$mount('#mail-admin-settings')
