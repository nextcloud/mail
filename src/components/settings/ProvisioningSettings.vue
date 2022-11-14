<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
		<h4>{{ t('mail','Configuration for "{provisioningDomain}"', {provisioningDomain}) }}</h4>
		<div class="form-preview-row">
			<form :id="'prov-form-' + setting.id" @submit.prevent="submitForm">
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'General') }}
					</div>
					<div class="group-inputs">
						<br>
						<label :for="'mail-provision-domain' + setting.id"> {{ t('mail', 'Provisioning domain') }}* </label>
						<br>
						<input
							:id="'mail-provision-domain' + setting.id"
							v-model="provisioningDomain"
							:disabled="loading"
							name="provisioningDomain"
							type="text">
						<br>
						<label :for="'mail-provision-email' + setting.id"> {{ t('mail', 'Email address template') }}* </label>
						<br>
						<input
							:id="'mail-provision-email' + setting.id"
							v-model="emailTemplate"
							:disabled="loading"
							name="emailTemplate"
							type="text">
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'IMAP') }}
					</div>
					<div class="group-inputs">
						<label :for="'mail-provision-imap-user' + setting.id">
							{{ t('mail', 'User') }}*
							<br>
							<input
								:id="'mail-provision-imap-user' + setting.id"
								v-model="imapUser"
								:disabled="loading"
								name="email"
								type="text">
						</label>
						<div class="flex-row">
							<label :for="'mail-provision-imap-host' + setting.id">
								{{ t('mail', 'Host') }}
								<br>
								<input
									:id="'mail-provision-imap-host' + setting.id"
									v-model="imapHost"
									:disabled="loading"
									name="email"
									type="text">
							</label>
							<label :for="'mail-provision-imap-port' + setting.id">
								{{ t('mail', 'Port') }}
								<br>
								<input
									:id="'mail-provision-imap-port' + setting.id"
									v-model="imapPort"
									:disabled="loading"
									name="email"
									type="number">
							</label>
						</div>
						<div class="flex-row">
							<input
								:id="'mail-provision-imap-user-none' + setting.id"
								v-model="imapSslMode"
								type="radio"
								name="man-imap-sec"
								:disabled="loading"
								value="none">
							<label
								class="button"
								:for="'mail-provision-imap-user-none' + setting.id"
								:class="{primary: imapSslMode === 'none'}">{{ t('mail', 'None') }}</label>
							<input
								:id="'mail-provision-imap-user-ssl' + setting.id"
								v-model="imapSslMode"
								type="radio"
								name="man-imap-sec"
								:disabled="loading"
								value="ssl">
							<label
								class="button"
								:for="'mail-provision-imap-user-ssl' + setting.id"
								:class="{primary: imapSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
							<input
								:id="'mail-provision-imap-user-tls' + setting.id"
								v-model="imapSslMode"
								type="radio"
								name="man-imap-sec"
								:disabled="loading"
								value="tls">
							<label
								class="button"
								:for="'mail-provision-imap-user-tls' + setting.id"
								:class="{primary: imapSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
						</div>
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'SMTP') }}
					</div>
					<div class="group-inputs">
						<label :for="'mail-provision-smtp-user' + setting.id">
							{{ t('mail', 'User') }}*
							<br>
							<input
								:id="'mail-provision-smtp-user' + setting.id"
								v-model="smtpUser"
								:disabled="loading"
								name="email"
								type="text">
						</label>
						<div class="flex-row">
							<label :for="'mail-provision-smtp-host' + setting.id">
								{{ t('mail', 'Host') }}
								<br>
								<input
									:id="'mail-provision-smtp-host' + setting.id"
									v-model="smtpHost"
									:disabled="loading"
									name="email"
									type="text">
							</label>
							<label :for="'mail-provision-smtp-port' + setting.id">
								{{ t('mail', 'Port') }}
								<br>
								<input
									:id="'mail-provision-smtp-port' + setting.id"
									v-model="smtpPort"
									:disabled="loading"
									name="email"
									type="number">
							</label>
						</div>
						<div class="flex-row">
							<input
								:id="'mail-provision-smtp-user-none' + setting.id"
								v-model="smtpSslMode"
								type="radio"
								name="man-smtp-sec"
								:disabled="loading"
								value="none">
							<label
								class="button"
								:for="'mail-provision-smtp-user-none' + setting.id"
								:class="{primary: smtpSslMode === 'none'}">{{ t('mail', 'None') }}</label>
							<input
								:id="'mail-provision-smtp-user-ssl' + setting.id"
								v-model="smtpSslMode"
								type="radio"
								name="man-smtp-sec"
								:disabled="loading"
								value="ssl">
							<label
								class="button"
								:for="'mail-provision-smtp-user-ssl' + setting.id"
								:class="{primary: smtpSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
							<input
								:id="'mail-provision-smtp-user-tls' + setting.id"
								v-model="smtpSslMode"
								type="radio"
								name="man-smtp-sec"
								:disabled="loading"
								value="tls">
							<label
								class="button"
								:for="'mail-provision-smtp-user-tls' + setting.id"
								:class="{primary: smtpSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
						</div>
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title">
						{{ t('mail', 'Sieve') }}
					</div>
					<div class="group-inputs">
						<div>
							<input
								:id="'mail-provision-sieve-enabled' + setting.id"
								v-model="sieveEnabled"
								type="checkbox"
								class="checkbox">
							<label :for="'mail-provision-sieve-enabled' + setting.id">
								{{ t('mail', 'Enable sieve integration') }}
							</label>
						</div>
						<label :for="'mail-provision-sieve-user' + setting.id">
							{{ t('mail', 'User') }}*
							<br>
							<input
								:id="'mail-provision-sieve-user' + setting.id"
								v-model="sieveUser"
								:disabled="loading"
								name="email"
								type="text">
						</label>
						<div class="flex-row">
							<label :for="'mail-provision-sieve-host' + setting.id">
								{{ t('mail', 'Host') }}
								<br>
								<input
									:id="'mail-provision-sieve-host' + setting.id"
									v-model="sieveHost"
									:disabled="loading"
									name="email"
									type="text">
							</label>
							<label :for="'mail-provision-sieve-port' + setting.id">
								{{ t('mail', 'Port') }}
								<br>
								<input
									:id="'mail-provision-sieve-port' + setting.id"
									v-model="sievePort"
									:disabled="loading"
									name="email"
									type="number">
							</label>
						</div>
						<div class="flex-row">
							<input
								:id="'mail-provision-sieve-user-none' + setting.id"
								v-model="sieveSslMode"
								type="radio"
								name="man-sieve-sec"
								:disabled="loading"
								value="none">
							<label
								class="button"
								:for="'mail-provision-sieve-user-none' + setting.id"
								:class="{primary: sieveSslMode === 'none'}">{{ t('mail', 'None') }}</label>
							<input
								:id="'mail-provision-sieve-user-ssl' + setting.id"
								v-model="sieveSslMode"
								type="radio"
								name="man-sieve-sec"
								:disabled="loading"
								value="ssl">
							<label
								class="button"
								:for="'mail-provision-sieve-user-ssl' + setting.id"
								:class="{primary: sieveSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
							<input
								:id="'mail-provision-sieve-user-tls' + setting.id"
								v-model="sieveSslMode"
								type="radio"
								name="man-sieve-sec"
								:disabled="loading"
								value="tls">
							<label
								class="button"
								:for="'mail-provision-sieve-user-tls' + setting.id"
								:class="{primary: sieveSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
						</div>
					</div>
				</div>
				<div v-if="ldapAliasesIntegration" class="settings-group">
					<div class="group-title">
						{{ t('mail', 'LDAP aliases integration') }}
					</div>
					<div class="group-inputs">
						<div>
							<input
								:id="'mail-provision-ldap-aliases-provisioning' + setting.id"
								v-model="ldapAliasesProvisioning"
								type="checkbox"
								class="checkbox">
							<label :for="'mail-provision-ldap-aliases-provisioning' + setting.id">
								{{ t('mail', 'Enable LDAP aliases integration') }}
							</label>
							<p>{{ t('mail', 'The LDAP aliases integration reads an attribute from the configured LDAP directory to provision email aliases.') }}</p>
						</div>
						<div>
							<label :for="'mail-provision-ldap-aliases-attribute' + setting.id">
								{{ t('mail', 'LDAP attribute for aliases') }}*
								<br>
								<input :id="'mail-provision-ldap-aliases-attribute' + setting.id"
									v-model="ldapAliasesAttribute"
									:disabled="loading"
									:required="ldapAliasesProvisioning"
									type="text">
							</label>
							<p>{{ t('mail', 'A multi value attribute to provision email aliases. For each value an alias is created. Aliases existing in Nextcloud which are not in the LDAP directory are deleted.') }}</p>
						</div>
					</div>
				</div>
				<div class="settings-group">
					<div class="group-title" />
					<div class="group-inputs">
						<Button
							class="config-button save-config"
							type="secondary"
							native-type="submit"
							:disabled="loading">
							<template #icon>
								<IconUpload :size="20" />
							</template>
							{{ t('mail', 'Save Config') }}
						</Button>
						<Button
							v-if="deleteButton"
							type="secondary"
							class="config-button"
							:disabled="loading"
							@click="disableConfig()">
							<template #icon>
								<IconDelete :size="20" />
							</template>
							{{ t('mail', 'Unprovision & Delete Config') }}
						</Button>
						<br>
						<small>{{
							t('mail', '* %USERID% and %EMAIL% will be replaced with the user\'s UID and email')
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
import { loadState } from '@nextcloud/initial-state'
import Button from '@nextcloud/vue/dist/Components/NcButton'

import IconUpload from 'vue-material-design-icons/Upload'
import IconDelete from 'vue-material-design-icons/Delete'

const ldapAliasesIntegration = loadState('mail', 'ldap_aliases_integration', false)

export default {
	name: 'ProvisioningSettings',
	components: {
		Button,
		ProvisionPreview,
		IconUpload,
		IconDelete,
	},
	props: {
		setting: {
			type: Object,
			required: true,
		},
		submit: {
			type: Function,
			required: true,
		},
		disable: {
			type: Function,
			required: false,
			default: undefined,
		},
		deleteButton: {
			type: Boolean,
			required: false,
			default: true,
		},
	},
	data() {
		return {
			active: !!this.setting.active,
			provisioningDomain: this.setting.provisioningDomain || '',
			emailTemplate: this.setting.emailTemplate || '',
			imapHost: this.setting.imapHost || 'mx.domain.com',
			imapPort: this.setting.imapPort || 993,
			imapUser: this.setting.imapUser || '%USERID%domain.com',
			imapSslMode: this.setting.imapSslMode || 'ssl',
			smtpHost: this.setting.smtpHost || 'mx.domain.com',
			smtpPort: this.setting.smtpPort || 587,
			smtpUser: this.setting.smtpUser || '%USERID%domain.com',
			smtpSslMode: this.setting.smtpSslMode || 'tls',
			sieveEnabled: this.setting.sieveEnabled || '',
			sieveHost: this.setting.sieveHost || '',
			sievePort: this.setting.sievePort || '',
			sieveSslMode: this.setting.sieveSslMode || '',
			sieveUser: this.setting.sieveUser || '',
			previewData1: {
				uid: 'user123',
				email: '',
			},
			previewData2: {
				uid: 'user321',
				email: 'user@domain.com',
			},
			ldapAliasesIntegration,
			ldapAliasesProvisioning: this.setting.ldapAliasesProvisioning || false,
			ldapAliasesAttribute: this.setting.ldapAliasesAttribute || '',
			loading: false,
		}
	},
	computed: {
		previewTemplates() {
			return {
				email: this.emailTemplate,
				provisioningDomain: this.provisioningDomain,
				imapUser: this.imapUser,
				imapHost: this.imapHost,
				imapPort: this.imapPort,
				imapSslMode: this.imapSslMode,
				smtpUser: this.smtpUser,
				smtpHost: this.smtpHost,
				smtpPort: this.smtpPort,
				smtpSslMode: this.smtpSslMode,
				sieveEnabled: this.sieveEnabled,
				sieveUser: this.sieveUser,
				sieveHost: this.sieveHost,
				sievePort: this.sievePort,
				sieveSslMode: this.sieveSslMode,
				ldapAliasesProvisioning: this.ldapAliasesProvisioning,
				ldapAliasesAttribute: this.ldapAliasesAttribute,
			}
		},
	},
	beforeMount() {
		logger.debug('provisioning setting loaded', { setting: this.setting })
	},
	methods: {
		async submitForm() {
			this.loading = true

			try {
				await this.submit({
					id: this.setting.id || null,
					active: this.setting.active || true,
					emailTemplate: this.emailTemplate,
					provisioningDomain: this.provisioningDomain,
					imapUser: this.imapUser,
					imapHost: this.imapHost,
					imapPort: this.imapPort,
					imapSslMode: this.imapSslMode,
					smtpUser: this.smtpUser,
					smtpHost: this.smtpHost,
					smtpPort: this.smtpPort,
					smtpSslMode: this.smtpSslMode,
					sieveEnabled: this.sieveEnabled,
					sieveUser: this.sieveUser,
					sieveHost: this.sieveHost,
					sievePort: this.sievePort,
					sieveSslMode: this.sieveSslMode,
					ldapAliasesProvisioning: this.ldapAliasesProvisioning,
					ldapAliasesAttribute: this.ldapAliasesAttribute,
				})

				logger.info('provisioning setting updated')
			} catch (error) {
				logger.error('Could not save provisioning setting', { error })
			} finally {
				this.loading = false
			}
		},
		async disableConfig() {
			this.loading = true
			try {
				await this.disable(this.setting.id)
			} catch (error) {
				logger.error('Could not delete provisioning setting', { error })
			} finally {
				this.loading = false
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
		max-width: 100px;
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
		.config-button {
			display: inline-block;
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
.save-config {
	margin-right: 6px;
}
</style>
