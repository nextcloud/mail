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
import './directives/drag-and-drop/styles/drag-and-drop.scss'
import VueShortKey from 'vue-shortkey'
import VTooltip from 'v-tooltip'
import VueClipboard from 'vue-clipboard2'

import App from './App'
import Nextcloud from './mixins/Nextcloud'
import router from './router'
import store from './store'
import { fixAccountId } from './service/AccountService'
import { loadState } from '@nextcloud/initial-state'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

sync(store, router)

Vue.mixin(Nextcloud)

Vue.use(VueShortKey, { prevent: ['input', 'div'] })
Vue.use(VTooltip)
Vue.use(VueClipboard)

const getPreferenceFromPage = (key) => {
	const elem = document.getElementById(key)
	if (!elem) {
		return
	}
	return elem.value
}

store.commit('savePreference', {
	key: 'debug',
	value: loadState('mail', 'debug', false),
})
store.commit('savePreference', {
	key: 'attachment-size-limit',
	value: Number.parseInt(getPreferenceFromPage('attachment-size-limit'), 10),
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
store.commit('savePreference', {
	key: 'tag-classified-messages',
	value: getPreferenceFromPage('tag-classified-messages'),
})

const accountSettings = loadState('mail', 'account-settings')
const accounts = loadState('mail', 'accounts', [])
const tags = loadState('mail', 'tags', [])
const outboxMessages = loadState('mail', 'outbox-messages')
const disableScheduledSend = loadState('mail', 'disable-scheduled-send')

accounts.map(fixAccountId).forEach((account) => {
	const settings = accountSettings.find(settings => settings.accountId === account.id)
	if (settings) {
		delete settings.accountId
		Object.entries(settings).forEach(([key, value]) => {
			store.commit('setAccountSetting', {
				accountId: account.id,
				key,
				value,
			})
		})
	}
	store.commit('addAccount', { ...account, ...settings })
})

tags.forEach(tag => store.commit('addTag', { tag }))

outboxMessages.forEach(message => store.commit('outbox/addMessage', { message }))

store.commit('setScheduledSendingDisabled', disableScheduledSend)

export default new Vue({
	el: '#content',
	router,
	store,
	render: (h) => h(App),
})
