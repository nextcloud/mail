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
	<SettingsSection
		:title="t( 'mail', 'Mail app')"
		:description="t( 'mail', 'The mail app allows users to read mails on their IMAP accounts.')">
		<p>
			{{
				t(
					'mail',
					'Here you can find instance-wide settings. User specific settings are found in the app itself (bottom-left corner).'
				)
			}}
		</p>
		<div class="ap-description">
			<h3>Account provisioning</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'A provisioning configuration will provision all accounts with a matching email address.'
						)
					}}
					{{
						t(
							'mail',
							'Using the wildcard (*) in the provisioning domain field will create a configuration that applies to all users, provided they do not match another configuration.'
						)
					}}
					<br>
					{{
						t(
							'mail',
							'The provisioning mechanism will prioritise specific domain configurations over the wildcard domain configuration.'
						)
					}}
					{{
						t(
							'mail',
							'Should a new matching configuration be found after the user was already provisioned with another configuration, the new configuration will take precedence and the user will be reprovisioned with the configuration.'
						)
					}}
					<br>
					{{
						t(
							'mail',
							'There can only be one configuration per domain and only one wildcard domain configuration.'
						)
					}}
					<br>
					{{
						t(
							'mail',
							'These settings can be used in conjunction with each other.'
						)
					}}
					<br>
					{{
						t(
							'mail',
							'If you only want to provision one domain for all users, use the wildcard (*).'
						)
					}}
					<br>
					{{
						t(
							'mail',
							"This setting only makes most sense if you use the same user back-end for your organization's Nextcloud and mail server."
						)
					}}
				</p>
			</article>
		</div>
		<h3>Provisioning Configurations</h3>
		<p>
			<button class="config-button icon-add" @click="addNew=true">
				{{ t('mail', 'Add new config') }}
			</button>
			<button class="config-button icon-settings" @click="provisionAll">
				{{ t('mail', 'Provision all accounts') }}
			</button>
			<ProvisioningSettings v-if="addNew"
				:key="formKey"
				:setting="preview"
				:submit="saveNewSettings"
				:delete-button="false" />
			<ProvisioningSettings v-for="setting in configs"
				:id="setting.id"
				:key="setting.id"
				:setting="setting"
				:submit="saveSettings"
				:disable="deleteProvisioning" />
		</p>
	</SettingsSection>
</template>

<script>
import logger from '../../logger'
import { showError, showSuccess } from '@nextcloud/dialogs'
import ProvisioningSettings from './ProvisioningSettings'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import {
	disableProvisioning,
	createProvisioningSettings,
	updateProvisioningSettings,
	provisionAll,

} from '../../service/SettingsService'
export default {
	name: 'AdminSettings',
	components: {
		ProvisioningSettings,
		SettingsSection,
	},
	props: {
		provisioningSettings: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			addNew: false,
			formKey: Math.random(),
			configs: this.provisioningSettings,
			preview: {
				provisioningDomain: '',
				emailTemplate: '',
				imapHost: 'mx.domain.com',
				imapPort: 993,
				imapUser: '%USERID%domain.com',
				imapSslMode: 'ssl',
				smtpHost: 'mx.domain.com',
				smtpPort: 587,
				smtpUser: '%USERID%domain.com',
				smtpSslMode: 'tls',
				previewData1: {
					uid: 'user123',
					email: '',
				},
				previewData2: {
					uid: 'user321',
					email: 'user@domain.com',
				},
				loading: false,
			},
		}
	},
	methods: {
		async saveSettings(settings) {
			try {
				await updateProvisioningSettings(settings)
				showSuccess(t('mail', 'Successfully updated config for "{domain}"', { domain: settings.provisioningDomain }))
			} catch (error) {
				showError(t('mail', 'Error saving config'))
				logger.error('Could not save provisioning setting', { error })
			}
		},
		async saveNewSettings(settings) {
			try {
				await createProvisioningSettings(settings)
				this.configs.unshift(settings)
				this.addNew = false
				this.resetForm()
				showSuccess(t('mail', 'Saved config for "{domain}"', { domain: settings.provisioningDomain }))
			} catch (error) {
				showError(t('mail', 'Could not save provisioning setting'))
				logger.error('Could not save provisioning setting', { error })
			}

		},
		resetForm() {
			this.formKey = Math.random()
		},
		async provisionAll() {
			try {
				const count = await provisionAll()
				showSuccess(n('mail', 'Successfully provisioned {count} account.', 'Successfully provisioned {count} accounts.', count.count, { count: count.count }))

			} catch (error) {
				showError(t('mail', 'There was an error when provisioning accounts.'))
				logger.error('Could not provision accounts', { error })
			}
		},
		async deleteProvisioning(id) {
			const deleted = this.configs.find(c => c.id === id)
			try {
				await disableProvisioning(id)
				logger.info('Deprovisioned successfully')
				this.configs = this.configs.filter(c => c.id !== id)
				showSuccess(t('mail', 'Successfully deleted and deprovisioned accounts for "{domain}"', { domain: deleted.provisioningDomain }))
			} catch (error) {
				showError(t('mail', 'Error when deleting and deprovisioning accounts for "{domain}"', { domain: deleted.provisioningDomain }))
				logger.error('Could not delete provisioning config', { error })
			}
		},
	},
}
</script>
<style lang="scss" scoped>
	.ap-description {
		margin-bottom: 24px;
	}
	.config-button {
		line-height: 24px;
		padding-left: 48px;
		padding-top: 6px;
		padding-bottom: 6px;
		background-position: 24px;
	}
</style>
