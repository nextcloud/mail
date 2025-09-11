/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { fixAccountId } from './service/AccountService.js'
import { loadState } from '@nextcloud/initial-state'
import { fetchAvailableLanguages } from './service/translationService.js'

import useOutboxStore from './store/outboxStore.js'
import useMainStore from './store/mainStore.js'

export default function initAfterAppCreation() {
	console.debug('Init after app creation')
	const mainStore = useMainStore()

	const preferences = loadState('mail', 'preferences', [])

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
		value: Number.parseInt(preferences['attachment-size-limit'], 10),
	})
	mainStore.savePreferenceMutation({
		key: 'version',
		value: preferences['config-installed-version'],
	})
	mainStore.savePreferenceMutation({
		key: 'external-avatars',
		value: preferences['external-avatars'],
	})
	mainStore.savePreferenceMutation({
		key: 'collect-data',
		value: preferences['collect-data'],
	})
	mainStore.savePreferenceMutation({
		key: 'search-priority-body',
		value: preferences['search-priority-body'],
	})
	const startMailboxId = preferences['start-mailbox-id']
	mainStore.savePreferenceMutation({
		key: 'start-mailbox-id',
		value: startMailboxId ? parseInt(startMailboxId, 10) : null,
	})
	mainStore.savePreferenceMutation({
		key: 'tag-classified-messages',
		value: preferences['tag-classified-messages'],
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
		value: preferences['layout-mode'],
	})
	mainStore.savePreferenceMutation({
		key: 'layout-message-view',
		value: preferences['layout-message-view'],
	})
	mainStore.savePreferenceMutation({
		key: 'follow-up-reminders',
		value: preferences['follow-up-reminders'],
	})
	mainStore.savePreferenceMutation({
		key: 'internal-addresses',
		value: loadState('mail', 'internal-addresses', false),
	})
	mainStore.savePreferenceMutation({
		key: 'smime-sign-aliases',
		value: loadState('mail', 'smime-sign-aliases', []),
	})

	mainStore.setQuickActions(loadState('mail', 'quick-actions', []))

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

	const outboxStore = useOutboxStore()
	outboxMessages.forEach(message => outboxStore.addMessageMutation({ message }))

	const llmTranslationEnabled = loadState('mail', 'llm_translation_enabled', false)
	mainStore.isTranslationEnabled = llmTranslationEnabled
	if (llmTranslationEnabled) {
		fetchAvailableLanguages().then(() => {})
	}
}
