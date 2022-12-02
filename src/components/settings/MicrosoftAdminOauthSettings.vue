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
		<label for="mail-microsoft-oauth-tenant-id"> {{ t('mail', 'Tenant ID (optional)') }} </label>
		<input
			id="mail-microsoft-oauth-tenant-id"
			v-model="tenantIdVal"
			:disabled="loading"
			type="text">
		<label for="mail-microsoft-oauth-client-id"> {{ t('mail', 'Client ID') }} </label>
		<input
			id="mail-microsoft-oauth-client-id"
			v-model="clientIdVal"
			:disabled="loading"
			type="text"
			required>
		<label for="mail-microsoft-oauth-client-secret"> {{ t('mail', 'Client secret') }} </label>
		<input
			id="mail-microsoft-oauth-client-secret"
			v-model="clientSecret"
			:disabled="loading"
			type="password"
			required>
		<button type="submit" :disabled="!clientIdVal || !clientSecret || clientSecret === PASSWORD_PLACEHOLDER || loading" class="primary">
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

import { configure, unlink } from '../../service/MicrosoftIntegrationService'
import logger from '../../logger'

const PASSWORD_PLACEHOLDER = '*****'

export default {
	name: 'MicrosoftAdminOauthSettings',
	props: {
		tenantId: {
			type: String,
			default: '',
		},
		clientId: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			loading: false,
			tenantIdVal: this.tenantId,
			clientIdVal: this.clientId,
			clientSecret: this.clientId ? PASSWORD_PLACEHOLDER : '',
			PASSWORD_PLACEHOLDER,
		}
	},
	methods: {
		async onSubmit() {
			this.loading = true
			try {
				await configure(this.tenantIdVal, this.clientIdVal, this.clientSecret)
				showSuccess(t('mail', 'Microsoft integration configured'))
			} catch (error) {
				logger.error('Could not configure Microsoft integration', { error })
				showError(t('mail', 'Could not configure Microsoft integration'))
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
				showSuccess(t('mail', 'Microsoft integration unlinked'))
			} catch (error) {
				logger.error('Could not unlink Microsoft integration', { error })
				showError(t('mail', 'Could not unlink Microsoft integration'))
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style scoped>

</style>
