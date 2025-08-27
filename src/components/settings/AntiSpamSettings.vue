<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
						<input id="mail-antispam-email-spam"
							v-model="email.spam"
							:disabled="loading"
							name="spam"
							type="email">
						<br>
						<label for="mail-antispam-email-ham"> {{ t('mail', '"Mark Not Junk" Email Address') }}* </label>
						<br>
						<input id="mail-antispam-email-ham"
							v-model="email.ham"
							:disabled="loading"
							name="ham"
							type="email">
						<br>
						<ButtonVue type="secondary"
							:aria-label="t('mail', 'Save')"
							:disabled="loading"
							native-type="submit"
							class="config-button">
							<template #icon>
								<IconUpload :size="20" />
							</template>
							{{ t('mail', 'Save') }}
						</ButtonVue>
						<ButtonVue :disabled="loading"
							:aria-label="t('mail', 'Reset')"
							class="config-button"
							type="secondary"
							@click="resetForm()">
							<template #icon>
								<IconDelete :size="20" />
							</template>
							{{ t('mail', 'Reset') }}
						</ButtonVue>
					</div>
				</div>
			</form>
		</div>
	</div>
</template>
<script>
import logger from '../../logger.js'
import { loadState } from '@nextcloud/initial-state'
import { setAntiSpamEmail, deleteAntiSpamEmail } from '../../service/SettingsService.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import ButtonVue from '@nextcloud/vue/components/NcButton'
import IconUpload from 'vue-material-design-icons/TrayArrowUp.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'

const email = loadState('mail', 'antispam_setting', '[]')

export default {
	name: 'AntiSpamSettings',
	components: {
		ButtonVue,
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
		text-align: end;
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
