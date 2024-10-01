/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line import/no-unresolved, n/no-missing-import
import 'vite/modulepreload-polyfill'

import { loadState } from '@nextcloud/initial-state'
import '@nextcloud/dialogs/style.css'
import Vue from 'vue'

import AdminSettings from './components/settings/AdminSettings.vue'
import Nextcloud from './mixins/Nextcloud.js'

Vue.mixin(Nextcloud)

const View = Vue.extend(AdminSettings)
new View({
	propsData: {
		provisioningSettings: loadState('mail', 'provisioning_settings') || [],
	},
}).$mount('#mail-admin-settings')
