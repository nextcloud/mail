<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form @submit.prevent="onSubmit">
		<label for="mail-gmail-oauth-client-id"> {{ t('mail', 'Client ID') }} </label>
		<input id="mail-gmail-oauth-client-id"
			v-model="clientIdVal"
			:disabled="loading"
			type="text"
			required>
		<label for="mail-gmail-oauth-client-secret"> {{ t('mail', 'Client secret') }} </label>
		<input id="mail-gmail-oauth-client-secret"
			v-model="clientSecret"
			:disabled="loading"
			type="password"
			required>
		<button type="submit" :disabled="!clientIdVal || !clientSecret || loading" class="primary">
			{{ t('mail', 'Save') }}
		</button>
		<button :disabled="loading" @click.prevent="onUnlink">
			{{ t('mail', 'Unlink') }}
		</button>
	</form>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import { configure, unlink } from '../../service/GoogleIntegrationService.js'
import logger from '../../logger.js'

const PASSWORD_PLACEHOLDER = '*****'

export default {
	name: 'GmailAdminOauthSettings',
	props: {
		clientId: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			loading: false,
			clientIdVal: this.clientId,
			clientSecret: this.clientId ? PASSWORD_PLACEHOLDER : '',
		}
	},
	methods: {
		async onSubmit() {
			this.loading = true
			try {
				await configure(this.clientIdVal, this.clientSecret)
				showSuccess(t('mail', 'Google integration configured'))
			} catch (error) {
				logger.error('Could not configure Google integration', { error })
				showError(t('mail', 'Could not configure Google integration'))
			} finally {
				this.loading = false
			}
		},
		async onUnlink() {
			this.loading = true
			try {
				await unlink()
				this.clientIdVal = ''
				this.clientSecret = ''
				showSuccess(t('mail', 'Google integration unlinked'))
			} catch (error) {
				logger.error('Could not unlink Google integration', { error })
				showError(t('mail', 'Could not unlink Google integration'))
			} finally {
				this.loading = false
			}
		},
	},
}
</script>
