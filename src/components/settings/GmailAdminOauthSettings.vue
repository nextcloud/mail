<!--
  - @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<form @submit.prevent="onSubmit">
		<label for="mail-gmail-oauth-client-id"> {{ t('mail', 'Client ID') }} </label>
		<input
			id="mail-gmail-oauth-client-id"
			v-model="clientIdVal"
			:disabled="loading"
			type="text"
			required>
		<label for="mail-gmail-oauth-client-secret"> {{ t('mail', 'Client secret') }} </label>
		<input
			id="mail-gmail-oauth-client-secret"
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

import { configure, unlink } from '../../service/GoogleIntegrationService'
import logger from '../../logger'

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

<style scoped>

</style>
