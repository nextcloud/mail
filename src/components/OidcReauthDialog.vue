<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		v-if="account"
		:open="true"
		:name="t('mail', 'Reconnect your mail account')"
		:buttons="buttons"
		@closing="dismiss">
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
import { translate as t } from '@nextcloud/l10n'
import { mapStores } from 'pinia'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import { CONSENT_ABORTED } from '../integration/oauth.js'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { findProviderForEmail, loadOidcProviders, openOidcConsent } from '../util/oidc.js'

export default {
	name: 'OidcReauthDialog',
	components: {
		NcDialog,
	},

	data() {
		return {
			dismissedIds: [],
			reconnecting: false,
			oidcProviders: loadOidcProviders(),
		}
	},

	computed: {
		...mapStores(useMainStore),

		/**
		 * The first flagged account the user has not dismissed this session.
		 *
		 * @return {?object}
		 */
		account() {
			return this.mainStore.getAccounts
				.find((account) => account.oauthNeedsReauth && !this.dismissedIds.includes(account.id)) ?? null
		},

		/**
		 * The configured provider for that account's email domain.
		 *
		 * @return {?object}
		 */
		provider() {
			return findProviderForEmail(this.account?.emailAddress, this.oidcProviders)
		},

		buttons() {
			return [
				{
					label: t('mail', 'Later'),
					type: 'tertiary',
					disabled: this.reconnecting,
					callback: () => { this.dismiss() },
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

		dismiss() {
			if (this.account) {
				this.dismissedIds = [...this.dismissedIds, this.account.id]
			}
		},

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
				await openOidcConsent(provider, account.id)
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
