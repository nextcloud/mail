<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<div>
		<h3>Account provisioning</h3>
		<p>
			{{
				t(
					'mail',
					'You can configure a template for account settings, from which all users will get an account provisioned from.'
				)
			}}
			{{
				t(
					'mail',
					"This setting only makes most sense if you use the same user back-end for your organization's Nextcloud and mail server."
				)
			}}
		</p>
		<div>
			<input id="mail-provision-toggle"
				v-model="active"
				type="checkbox"
				class="checkbox">
			<label for="mail-provision-toggle">
				{{ t('mail', 'Provision an account for every user') }}
			</label>
		</div>
		<div v-if="active" class="form-preview-row">
			<form @submit.prevent="submit">
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'General') }}
					</div>
					<div class="group-inputs">
						<label for="mail-provision-email"> {{ t('mail', 'Email address') }}* </label>
						<br>
						<input
							id="mail-provision-email"
							v-model="emailTemplate"
							:disabled="loading"
							name="email"
							type="text">
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'IMAP') }}
					</div>
					<div class="group-inputs">
						<label for="mail-provision-imap-user">
							{{ t('mail', 'User') }}*
							<br>
							<input
								id="mail-provision-imap-user"
								v-model="imapUser"
								:disabled="loading"
								name="email"
								type="text">
						</label>
						<div class="flex-row">
							<label for="mail-provision-imap-host">
								{{ t('mail', 'Host') }}
								<br>
								<input
									id="mail-provision-imap-host"
									v-model="imapHost"
									:disabled="loading"
									name="email"
									type="text">
							</label>
							<label for="mail-provision-imap-port">
								{{ t('mail', 'Port') }}
								<br>
								<input
									id="mail-provision-imap-port"
									v-model="imapPort"
									:disabled="loading"
									name="email"
									type="number">
							</label>
						</div>
						<div class="flex-row">
							<input
								id="mail-provision-imap-user-none"
								v-model="imapSslMode"
								type="radio"
								name="man-imap-sec"
								:disabled="loading"
								value="none">
							<label
								class="button"
								for="mail-provision-imap-user-none"
								:class="{primary: imapSslMode === 'none'}">{{ t('mail', 'None') }}</label>
							<input
								id="mail-provision-imap-user-ssl"
								v-model="imapSslMode"
								type="radio"
								name="man-imap-sec"
								:disabled="loading"
								value="ssl">
							<label
								class="button"
								for="mail-provision-imap-user-ssl"
								:class="{primary: imapSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
							<input
								id="mail-provision-imap-user-tls"
								v-model="imapSslMode"
								type="radio"
								name="man-imap-sec"
								:disabled="loading"
								value="tls">
							<label
								class="button"
								for="mail-provision-imap-user-tls"
								:class="{primary: imapSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
						</div>
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'SMTP') }}
					</div>
					<div class="group-inputs">
						<label for="mail-provision-smtp-user">
							{{ t('mail', 'User') }}*
							<br>
							<input
								id="mail-provision-smtp-user"
								v-model="smtpUser"
								:disabled="loading"
								name="email"
								type="text">
						</label>
						<div class="flex-row">
							<label for="mail-provision-imap-host">
								{{ t('mail', 'Host') }}
								<br>
								<input
									id="mail-provision-smtp-host"
									v-model="smtpHost"
									:disabled="loading"
									name="email"
									type="text">
							</label>
							<label for="mail-provision-smtp-port">
								{{ t('mail', 'Port') }}
								<br>
								<input
									id="mail-provision-smtp-port"
									v-model="smtpPort"
									:disabled="loading"
									name="email"
									type="number">
							</label>
						</div>
						<div class="flex-row">
							<input
								id="mail-provision-smtp-user-none"
								v-model="smtpSslMode"
								type="radio"
								name="man-smtp-sec"
								:disabled="loading"
								value="none">
							<label
								class="button"
								for="mail-provision-smtp-user-none"
								:class="{primary: smtpSslMode === 'none'}">{{ t('mail', 'None') }}</label>
							<input
								id="mail-provision-smtp-user-ssl"
								v-model="smtpSslMode"
								type="radio"
								name="man-smtp-sec"
								:disabled="loading"
								value="ssl">
							<label
								class="button"
								for="mail-provision-smtp-user-ssl"
								:class="{primary: smtpSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
							<input
								id="mail-provision-smtp-user-tls"
								v-model="smtpSslMode"
								type="radio"
								name="man-smtp-sec"
								:disabled="loading"
								value="tls">
							<label
								class="button"
								for="mail-provision-smtp-user-tls"
								:class="{primary: smtpSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
						</div>
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title" />
					<div class="group-inputs">
						<input
							type="submit"
							class="primary"
							:disabled="loading"
							:value="t('mail', 'Apply and create/update for all users')">
						<input
							type="button"
							:disabled="loading"
							:value="t('mail', 'Disable and un-provision existing accounts')"
							@click="disable">
						<br>
						<small>{{
							t('mail', "* %USERID% and %EMAIL% will be replaced with the user's UID and email")
						}}</small>
					</div>
				</div>
			</form>
			<div>
				<h4>Preview</h4>
				<p>
					{{
						t('mail', 'With the settings above, the app will create account settings in the following way:')
					}}
				</p>
				<div class="previews">
					<ProvisionPreview class="preview-item" :templates="previewTemplates" :data="previewData1" />
					<ProvisionPreview class="preview-item" :templates="previewTemplates" :data="previewData2" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import logger from '../../logger'
import ProvisionPreview from './ProvisionPreview'
import { disableProvisioning, saveProvisioningSettings } from '../../service/SettingsService'

export default {
	name: 'ProvisioningSettings',
	components: { ProvisionPreview },
	props: {
		settings: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			active: !!this.settings.active,
			emailTemplate: this.settings.email || '',
			imapHost: this.settings.imapHost || 'mx.domain.com',
			imapPort: this.settings.imapPort || 993,
			imapUser: this.settings.imapUser || '%USERID%domain.com',
			imapSslMode: this.settings.imapSslMode || 'ssl',
			smtpHost: this.settings.smtpHost || 'mx.domain.com',
			smtpPort: this.settings.smtpPort || 587,
			smtpUser: this.settings.smtpUser || '%USERID%domain.com',
			smtpSslMode: this.settings.smtpSslMode || 'tls',
			previewData1: {
				uid: 'user123',
				email: '',
			},
			previewData2: {
				uid: 'user321',
				email: 'user@domain.com',
			},
			loading: false,
		}
	},
	computed: {
		previewTemplates() {
			return {
				email: this.emailTemplate,
				imapUser: this.imapUser,
				imapHost: this.imapHost,
				imapPort: this.imapPort,
				imapSslMode: this.imapSslMode,
				smtpUser: this.smtpUser,
				smtpHost: this.smtpHost,
				smtpPort: this.smtpPort,
				smtpSslMode: this.smtpSslMode,
			}
		},
	},
	beforeMount() {
		logger.debug('provisioning settings loaded', { settings: this.settings })
	},
	methods: {
		submit() {
			this.loading = true

			return saveProvisioningSettings({
				emailTemplate: this.emailTemplate,
				imapUser: this.imapUser,
				imapHost: this.imapHost,
				imapPort: this.imapPort,
				imapSslMode: this.imapSslMode,
				smtpUser: this.smtpUser,
				smtpHost: this.smtpHost,
				smtpPort: this.smtpPort,
				smtpSslMode: this.smtpSslMode,
			})
				.then(() => {
					logger.info('provisioning settings updated')
				})
				.catch((error) => {
					// TODO: show user feedback
					logger.error('Could not save provisioning settings', { error })
				})
				.then(() => {
					this.loading = false
				})
		},
		disable() {
			this.loading = true

			return disableProvisioning()
				.then(() => {
					logger.info('deprovisioned successfully')
				})
				.catch((error) => {
					logger.error('could not deprovision accounts', { error })
				})
				.then(() => {
					this.active = false
					this.loading = false
				})
		},
	},
}
</script>

<style lang="scss" scoped>
.form-preview-row {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;

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

		input[type='text'] {
			min-width: 200px;
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
