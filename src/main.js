/*
 * @copyright Copyright (c) 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import App from './App'
import {getRequestToken} from '@nextcloud/auth'
import router from './router'
import store from './store'
import {sync} from 'vuex-router-sync'
import {translate, translatePlural} from '@nextcloud/l10n'
import {generateFilePath} from '@nextcloud/router'
import VueShortKey from 'vue-shortkey'
import VTooltip from 'v-tooltip'

import {fixAccountId} from './service/AccountService'

__webpack_nonce__ = btoa(getRequestToken())
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

sync(store, router)

Vue.mixin({
	methods: {
		t: translate,
		n: translatePlural,
	},
})

Vue.use(VueShortKey, {prevent: ['input', 'textarea']})
Vue.use(VTooltip)

const getPreferenceFromPage = key => {
	const elem = document.getElementById(key)
	if (!elem) {
		return
	}
	return elem.value
}

store.commit('savePreference', {
	key: 'debug',
	value: getPreferenceFromPage('debug-mode'),
})
store.commit('savePreference', {
	key: 'version',
	value: getPreferenceFromPage('config-installed-version'),
})
store.commit('savePreference', {
	key: 'external-avatars',
	value: getPreferenceFromPage('external-avatars'),
})

const accounts = JSON.parse(atob(getPreferenceFromPage('serialized-accounts')))
accounts.map(fixAccountId).forEach(account => {
	const folders = account.folders
	store.commit('addAccount', account)
})

new Vue({
	el: '#content',
	router,
	store,
	render: h => h(App),
})
