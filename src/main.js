/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'
import { registerDavProperty } from '@nextcloud/files'
import { sync } from 'vuex-router-sync'
import { generateFilePath } from '@nextcloud/router'
import '@nextcloud/dialogs/style.css'
import './directives/drag-and-drop/styles/drag-and-drop.scss'
import VueShortKey from 'vue-shortkey'
import vToolTip from 'v-tooltip'

import App from './App.vue'
import Nextcloud from './mixins/Nextcloud.js'
import router from './router.js'
import store from './store/index.js'
import { fixAccountId } from './service/AccountService.js'
import { loadState } from '@nextcloud/initial-state'
import { createPinia, PiniaVuePlugin } from 'pinia'
import useOutboxStore from './store/outboxStore.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

sync(store, router)

Vue.mixin(Nextcloud)

Vue.use(VueShortKey, { prevent: ['input', 'div', 'textarea'] })
Vue.use(vToolTip)

const getPreferenceFromPage = (key) => {
	const elem = document.getElementById(key)
	if (!elem) {
		return
	}
	return elem.value
}

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })

store.commit('savePreference', {
	key: 'debug',
	value: loadState('mail', 'debug', false),
})
store.commit('savePreference', {
	key: 'ncVersion',
	value: loadState('mail', 'ncVersion'),
})

store.commit('savePreference', {
	key: 'sort-order',
	value: loadState('mail', 'sort-order', 'newest'),
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
	key: 'search-priority-body',
	value: getPreferenceFromPage('search-priority-body'),
})
const startMailboxId = getPreferenceFromPage('start-mailbox-id')
store.commit('savePreference', {
	key: 'start-mailbox-id',
	value: startMailboxId ? parseInt(startMailboxId, 10) : null,
})
store.commit('savePreference', {
	key: 'tag-classified-messages',
	value: getPreferenceFromPage('tag-classified-messages'),
})
store.commit('savePreference', {
	key: 'allow-new-accounts',
	value: loadState('mail', 'allow-new-accounts', true),
})
store.commit('savePreference', {
	key: 'password-is-unavailable',
	value: loadState('mail', 'password-is-unavailable', false),
})
store.commit('savePreference', {
	key: 'layout-mode',
	value: getPreferenceFromPage('layout-mode'),
})
store.commit('savePreference', {
	key: 'follow-up-reminders',
	value: getPreferenceFromPage('follow-up-reminders'),
})

const accountSettings = loadState('mail', 'account-settings')
const accounts = loadState('mail', 'accounts', [])
const tags = loadState('mail', 'tags', [])
const outboxMessages = loadState('mail', 'outbox-messages')
const disableScheduledSend = loadState('mail', 'disable-scheduled-send')
const disableSnooze = loadState('mail', 'disable-snooze')
const googleOauthUrl = loadState('mail', 'google-oauth-url', null)
const microsoftOauthUrl = loadState('mail', 'microsoft-oauth-url', null)
const followUpFeatureAvailable = loadState('mail', 'llm_followup_available', false)

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

store.commit('setScheduledSendingDisabled', disableScheduledSend)
store.commit('setSnoozeDisabled', disableSnooze)
store.commit('setGoogleOauthUrl', googleOauthUrl)
store.commit('setMicrosoftOauthUrl', microsoftOauthUrl)
store.commit('setFollowUpFeatureAvailable', followUpFeatureAvailable)

const smimeCertificates = loadState('mail', 'smime-certificates', [])
store.commit('setSmimeCertificates', smimeCertificates)

/* eslint-disable vue/match-component-file-name */
export default new Vue({
	el: '#content',
	name: 'Mail',
	router,
	store,
	pinia,
	render: (h) => h(App),
})

const outboxStore = useOutboxStore()
outboxMessages.forEach(message => outboxStore.addMessageMutation({ message }))
