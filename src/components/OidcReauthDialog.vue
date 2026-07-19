<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		v-if="account && !dismissed"
		:open="true"
		:name="t('mail', 'Reconnect your mail account')"
		:buttons="buttons"
		@closing="dismissed = true">
		<p>
			{{
				t(
					'mail',
					'The sign-in for {email} expired and could not be renewed automatically. Reconnect to keep this account in sync.',
					{ email: account.emailAddress },
				)
			}}
		</p>
	</NcDialog>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { mapStores } from 'pinia'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import { CONSENT_ABORTED, getUserConsent } from '../integration/oauth.js'
import logger from '../logger.js'
import { generateOauthState } from '../service/OauthStateService.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'OidcReauthDialog',
	components: {
		NcDialog,
	},

	data() {
		return {
			dismissed: false,
			reconnecting: false,
			oidcProviders: loadState('mail', 'oidc_providers', []),
		}
	},

	computed: {
		...mapStores(useMainStore),

		/**
		 * The first account whose OIDC grant can no longer be renewed.
		 *
		 * @return {?object}
		 */
		account() {
			return this.mainStore.getAccounts.find((account) => account.oauthNeedsReauth) ?? null
		},

		/**
		 * The configured provider for that account's email domain.
		 *
		 * @return {?object}
		 */
		provider() {
			if (!this.account?.emailAddress) {
				return null
			}
			const at = this.account.emailAddress.lastIndexOf('@')
			if (at === -1) {
				return null
			}
			const domain = this.account.emailAddress.slice(at + 1).toLowerCase()
			return this.oidcProviders.find((p) => (p.emailDomain || '').toLowerCase() === domain) ?? null
		},

		buttons() {
			return [
				{
					label: t('mail', 'Later'),
					type: 'tertiary',
					disabled: this.reconnecting,
					callback: () => { this.dismissed = true },
				},
				{
					label: t('mail', 'Reconnect'),
					type: 'primary',
					disabled: this.reconnecting || !this.provider,
					callback: () => { this.reconnect() },
				},
			]
		},
	},

	methods: {
		t,

		async reconnect() {
			// Both are computed from the flagged account, which changes once it is
			// patched below, so hold on to them for the whole flow.
			const account = this.account
			const provider = this.provider
			if (!account || !provider) {
				return
			}
			this.reconnecting = true
			try {
				// Opened from the button click so the popup carries user activation
				await getUserConsent(provider.authorizeUrl.replace('_state_', await generateOauthState(account.id)))
				// The server already cleared the flag when it stored the new tokens.
				// Patch the one account instead of refetching the list: re-adding
				// accounts that are already loaded appends duplicate ids to it.
				this.mainStore.patchAccountMutation({ account, data: { oauthNeedsReauth: false } })

				// Nothing could be synced while the grant was dead, so the mailbox list
				// is empty until it is pulled again. The account is reconnected either
				// way, so a failure here must not read as a failed reconnect.
				try {
					await this.mainStore.syncMailboxesForAccount(account)
				} catch (error) {
					logger.error('Could not sync mailboxes after reconnecting', { error })
				}
				showSuccess(t('mail', 'Account reconnected'))
			} catch (error) {
				if (error === CONSENT_ABORTED || error?.message === CONSENT_ABORTED) {
					logger.info('OIDC re-authentication aborted by the user')
					return
				}
				logger.error('Could not reconnect OIDC account', { error })
				showError(t('mail', 'Could not reconnect the account'))
			} finally {
				this.reconnecting = false
			}
		},
	},
}
</script>
