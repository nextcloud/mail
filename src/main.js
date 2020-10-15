/**
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
import { getRequestToken } from '@nextcloud/auth'
import { sync } from 'vuex-router-sync'
import { generateFilePath } from '@nextcloud/router'
import '@nextcloud/dialogs/styles/toast.scss'
import VueShortKey from 'vue-shortkey'
import VTooltip from 'v-tooltip'

import App from './App'
import Nextcloud from './mixins/Nextcloud'
import router from './router'
import store from './store'
import { fixAccountId } from './service/AccountService'
import { Base64 } from 'js-base64'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

sync(store, router)

Vue.mixin(Nextcloud)

Vue.use(VueShortKey, { prevent: ['input', 'div'] })
Vue.use(VTooltip)

const getPreferenceFromPage = (key) => {
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
store.commit('savePreference', {
	key: 'collect-data',
	value: getPreferenceFromPage('collect-data'),
})

const accounts = JSON.parse(Base64.decode(getPreferenceFromPage('serialized-accounts')))
accounts.map(fixAccountId).forEach((account) => {
	store.commit('addAccount', account)
})

export default new Vue({
	el: '#content',
	router,
	store,
	render: (h) => h(App),
})
