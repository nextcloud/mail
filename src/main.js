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
import useMainStore from './store/mainStore.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('mail', '', 'js/')

Vue.use(PiniaVuePlugin)
const pinia = createPinia()
const mainStore = useMainStore()

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

mainStore.savePreferenceMutation({
	key: 'debug',
	value: loadState('mail', 'debug', false),
})
mainStore.savePreferenceMutation({
	key: 'ncVersion',
	value: loadState('mail', 'ncVersion'),
})

mainStore.savePreferenceMutation({
	key: 'sort-order',
	value: loadState('mail', 'sort-order', 'newest'),
})

mainStore.savePreferenceMutation({
	key: 'attachment-size-limit',
	value: Number.parseInt(getPreferenceFromPage('attachment-size-limit'), 10),
})
mainStore.savePreferenceMutation({
	key: 'version',
	value: getPreferenceFromPage('config-installed-version'),
})
mainStore.savePreferenceMutation({
	key: 'external-avatars',
	value: getPreferenceFromPage('external-avatars'),
})
mainStore.savePreferenceMutation({
	key: 'collect-data',
	value: getPreferenceFromPage('collect-data'),
})
mainStore.savePreferenceMutation({
	key: 'search-priority-body',
	value: getPreferenceFromPage('search-priority-body'),
})
const startMailboxId = getPreferenceFromPage('start-mailbox-id')
mainStore.savePreferenceMutation({
	key: 'start-mailbox-id',
	value: startMailboxId ? parseInt(startMailboxId, 10) : null,
})
mainStore.savePreferenceMutation({
	key: 'tag-classified-messages',
	value: getPreferenceFromPage('tag-classified-messages'),
})
mainStore.savePreferenceMutation({
	key: 'allow-new-accounts',
	value: loadState('mail', 'allow-new-accounts', true),
})
mainStore.savePreferenceMutation({
	key: 'password-is-unavailable',
	value: loadState('mail', 'password-is-unavailable', false),
})
mainStore.savePreferenceMutation({
	key: 'layout-mode',
	value: getPreferenceFromPage('layout-mode'),
})
mainStore.savePreferenceMutation({
	key: 'follow-up-reminders',
	value: getPreferenceFromPage('follow-up-reminders'),
})
mainStore.savePreferenceMutation({
	key: 'internal-addresses',
	value: loadState('mail', 'internal-addresses', false),
})

const accountSettings = loadState('mail', 'account-settings')
const accounts = loadState('mail', 'accounts', [])
const internalAddressesList = loadState('mail', 'internal-addresses-list', [])
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
			mainStore.setAccountSettingMutation({
				accountId: account.id,
				key,
				value,
			})
		})
	}
	mainStore.addAccountMutation({ ...account, ...settings })
})

tags.forEach(tag => mainStore.addTagMutation({ tag }))
internalAddressesList.forEach(internalAddress => mainStore.addInternalAddressMutation(internalAddress))

mainStore.setScheduledSendingDisabledMutation(disableScheduledSend)
mainStore.setSnoozeDisabledMutation(disableSnooze)
mainStore.setGoogleOauthUrlMutation(googleOauthUrl)
mainStore.setMicrosoftOauthUrlMutation(microsoftOauthUrl)
mainStore.setFollowUpFeatureAvailableMutation(followUpFeatureAvailable)

const smimeCertificates = loadState('mail', 'smime-certificates', [])
mainStore.setSmimeCertificatesMutation(smimeCertificates)

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
