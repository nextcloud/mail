<!--
  - @copyright 2021 Anna Larch <anna@nextcloud.com>
  -
  - @author Anna Larch <anna@nextcloud.com>
  -
  - @license AGPL-3.0-or-later
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
	<div class="section">
		<h4>{{ t('mail','Anti Spam') }}</h4>
		<p>
			{{ t('mail', 'Add the email address of your anti spam report service here.') }}
		</p>
		<p>
			{{ t('mail', 'When using this setting, a report email will be sent to the SPAM report server when a user clicks "Mark as spam".') }}
			{{ t('mail', 'The original message will be attached as a "message/rfc822" attachment.') }}
		</p>
		<div class="form-preview-row">
			<form id="antispam-form" @submit.prevent.stop="submitForm">
				<div class="settings-group">
					<div class="group-inputs">
						<label for="mail-antispam-email-spam"> {{ t('mail', '"Mark as Spam" Email Address') }}* </label>
						<br>
						<input
							id="mail-antispam-email-spam"
							v-model="email.spam"
							:disabled="loading"
							name="spam"
							type="email">
						<br>
						<label for="mail-antispam-email-ham"> {{ t('mail', '"Mark Not Junk" Email Address') }}* </label>
						<br>
						<input
							id="mail-antispam-email-ham"
							v-model="email.ham"
							:disabled="loading"
							name="ham"
							type="email">
						<br>
						<Button
							type="secondary"
							:disabled="loading"
							native-type="submit"
							class="config-button">
							<template #icon>
								<IconUpload :size="20" />
							</template>
							{{ t('mail', 'Save') }}
						</Button>
						<Button
							:disabled="loading"
							class="config-button"
							type="secondary"
							@click="resetForm()">
							<template #icon>
								<IconDelete :size="20" />
							</template>
							{{ t('mail', 'Reset') }}
						</Button>
					</div>
				</div>
			</form>
		</div>
	</div>
</template>
<script>
import logger from '../../logger'
import { loadState } from '@nextcloud/initial-state'
import { setAntiSpamEmail, deleteAntiSpamEmail } from '../../service/SettingsService'
import { showError, showSuccess } from '@nextcloud/dialogs'
import Button from '@nextcloud/vue/dist/Components/NcButton'
import IconUpload from 'vue-material-design-icons/Upload'
import IconDelete from 'vue-material-design-icons/Delete'

const email = loadState('mail', 'antispam_setting', '[]')

export default {
	name: 'AntiSpamSettings',
	components: {
		Button,
		IconUpload,
		IconDelete,
	},
	data() {
		return {
			email,
			loading: false,
		}
	},
	methods: {
		async submitForm() {
			this.loading = true

			try {
				await setAntiSpamEmail(email)
				logger.info('anti spam email updated')
				showSuccess(t('mail', 'Successfully set up anti spam email addresses'))
			} catch (error) {
				logger.error('Could not save email setting', { error })
				showError(t('mail', 'Error saving anti spam email addresses'))
			} finally {
				this.loading = false
			}
		},
		async resetForm() {
			this.loading = true
			try {
				await deleteAntiSpamEmail()
				logger.info('anti spam email deleted')
				showSuccess(t('mail', 'Successfully deleted anti spam reporting email'))
			} catch (error) {
				logger.error('Could not delete email setting', { error })
				showError(t('mail', 'Error deleting anti spam reporting email'))

			} finally {
				this.loading = false
				this.email = []
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.form-preview-row {
	display: flex;

	div:last-child {
		margin-top: 10px;
	}
}

.settings-group {
	display: flex;
	flex-direction: row;
	flex-wrap: nowrap;

	.group-title {
		min-width: 100px;
		text-align: right;
		margin: 10px;
		font-weight: bold;
	}
	.group-inputs {
		margin: 10px;
		flex-grow: 1;
		.config-button {
			display: inline-block;
			margin-top: 10px;
			margin-inline: 4px;
		}
	}
}

h4 {
	font-weight: bold;
}

.previews {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	margin: 0 -10px;

	.preview-item {
		flex-grow: 1;
		margin: 10px;
		padding: 25px;
	}
}
input[type='radio'] {
	display: none;
}

.flex-row {
	display: flex;
}
form {
	label {
		color: var(--color-text-maxcontrast);
	}
}
</style>
