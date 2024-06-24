/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'

import Nextcloud from './mixins/Nextcloud.js'
import DashboardImportant from './views/DashboardImportant.vue'
import DashboardUnread from './views/DashboardUnread.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.mixin(Nextcloud)

document.addEventListener('DOMContentLoaded', function() {
	const register = OCA?.Dashboard?.register || (() => {})

	register('mail', (el) => {
		const View = Vue.extend(DashboardImportant)
		new View().$mount(el)
	})

	register('mail-unread', (el) => {
		const View = Vue.extend(DashboardUnread)
		new View().$mount(el)
	})
})
