<!--
  - @copyright 2023 Micke Nordin <kano@sunet.se>
  -
  - @author 2023 Micke Nordin <kano@sunet.se>
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
		<label for="mail-master-password"> {{ t('mail', 'Master password') }} </label>
		<input
			id="mail-master-password"
			v-model="masterPassword"
			:disabled="loading"
			type="password"
			required>
		<button type="submit" :disabled="!masterPassword || loading" class="primary">
			{{ t('mail', 'Save') }}
		</button>
		<button :disabled="loading" @click.prevent="onRemove">
			{{ t('mail', 'Remove') }}
		</button>
	</form>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import { configure, remove } from '../../service/MasterPasswordService'
import logger from '../../logger'

const PASSWORD_PLACEHOLDER = '*****'

export default {
	name: 'MasterPasswordSettings',
	props: {
		masterPassword: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			loading: false,
			masterPassword: this.masterPassword ? PASSWORD_PLACEHOLDER : '',
		}
	},
	methods: {
		async onSubmit() {
			this.loading = true
			try {
				await configure(this.masterPassword)
				showSuccess(t('mail', 'Master password configured'))
			} catch (error) {
				logger.error('Could not configure master password', { error })
				showError(t('mail', 'Could not configure master password'))
			} finally {
				this.loading = false
			}
		},
		async onRemove() {
			this.loading = true
			try {
				await remove()
				this.masterPassword = ''
				showSuccess(t('mail', 'Master password removed'))
			} catch (error) {
				logger.error('Could not remove master password', { error })
				showError(t('mail', 'Could not remove master password'))
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style scoped>

</style>
