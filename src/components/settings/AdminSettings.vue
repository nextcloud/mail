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
		<div class="app-description">
			<h3>
				{{
					t(
						'mail',
						'Account provisioning'
					)
				}}
			</h3>
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
							'This setting only makes most sense if you use the same user back-end for your Nextcloud and mail server of your organization.'
						)
					}}
				</p>
			</article>
		</div>
		<h3>
			{{
				t(
					'mail',
					'Provisioning Configurations'
				)
			}}
		</h3>
		<p>
			<Button class="config-button" @click="addNew=true">
				<template #icon>
					<IconAdd :size="20" />
				</template>
				{{ t('mail', 'Add new config') }}
			</Button>
			<Button class="config-button" @click="provisionAll">
				<template #icon>
					<IconSettings :size="20" />
				</template>
				{{ t('mail', 'Provision all accounts') }}
			</Button>
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
		<div class="app-description">
			<h3>{{ t('mail', 'Allow additional mail accounts') }}</h3>
			<article>
				<p>
					<NcCheckboxRadioSwitch
						:checked.sync="allowNewMailAccounts"
						type="switch"
						@update:checked="updateAllowNewMailAccounts">
						{{ t('mail','Allow additional Mail accounts from User Settings') }}
					</NcCheckboxRadioSwitch>
				</p>
			</article>
		</div>
		<div class="app-description">
			<h3>
				{{
					t(
						'mail',
						'Anti Spam Service'
					)
				}}
			</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'You can set up an anti spam service email address here.'
						)
					}}
					<br>
					{{
						t(
							'mail',
							'Any email that is marked as spam will be sent to the anti spam service.'
						)
					}}
				</p>
			</article>
			<AntiSpamSettings />
		</div>
		<div class="app-description">
			<h3>
				{{
					t(
						'mail',
						'Gmail integration'
					)
				}}
			</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'Gmail allows users to access their email via IMAP. For security reasons this access is only possible with an OAuth 2.0 connection or Google accounts that use two-factor authentication and app passwords.'
						)
					}}
				</p>
				<p>
					{{
						t(
							'mail',
							'You have to register a new Client ID for a "Web application" in the Google Cloud console. Add the URL {url} as authorized redirect URI.',
							{
								url: googleOauthRedirectUrl,
							}
						)
					}}
				</p>
			</article>
			<GmailAdminOauthSettings :client-id="googleOauthClientId" />
		</div>
		<div class="app-description">
			<h3>
				{{
					t(
						'mail',
						'Microsoft integration'
					)
				}}
			</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'Microsoft allows users to access their email via IMAP. For security reasons this access is only possible with an OAuth 2.0 connection.'
						)
					}}
				</p>
				<p>
					{{
						t(
							'mail',
							'You have to register a new app in the Microsoft Azure Active Directory portal. Add the URL {url} as redirect URI.',
							{
								url: microsoftOauthRedirectUrl,
							}
						)
					}}
				</p>
			</article>
			<MicrosoftAdminOauthSettings :tenant-id="microsoftOauthTenantId" :client-id="microsoftOauthClientId" />
		</div>
	</SettingsSection>
</template>

<script>
import Button from '@nextcloud/vue/dist/Components/NcButton'
import GmailAdminOauthSettings from './GmailAdminOauthSettings'
import logger from '../../logger'
import MicrosoftAdminOauthSettings from './MicrosoftAdminOauthSettings'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import ProvisioningSettings from './ProvisioningSettings'
import AntiSpamSettings from './AntiSpamSettings'
import IconAdd from 'vue-material-design-icons/Plus'
import IconSettings from 'vue-material-design-icons/Cog'
import SettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch'
import {
	disableProvisioning,
	createProvisioningSettings,
	updateProvisioningSettings,
	provisionAll,
	updateAllowNewMailAccounts,

} from '../../service/SettingsService'

const googleOauthClientId = loadState('mail', 'google_oauth_client_id', null) ?? undefined
const googleOauthRedirectUrl = loadState('mail', 'google_oauth_redirect_url', null)
const microsoftOauthTenantId = loadState('mail', 'microsoft_oauth_tenant_id', null) ?? undefined
const microsoftOauthClientId = loadState('mail', 'microsoft_oauth_client_id', null) ?? undefined
const microsoftOauthRedirectUrl = loadState('mail', 'microsoft_oauth_redirect_url', null)

export default {
	name: 'AdminSettings',
	components: {
		GmailAdminOauthSettings,
		AntiSpamSettings,
		MicrosoftAdminOauthSettings,
		ProvisioningSettings,
		SettingsSection,
		Button,
		IconAdd,
		IconSettings,
		NcCheckboxRadioSwitch,
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
			googleOauthClientId,
			googleOauthRedirectUrl,
			microsoftOauthTenantId,
			microsoftOauthClientId,
			microsoftOauthRedirectUrl,
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
			allowNewMailAccounts: loadState('mail', 'allow_new_mail_accounts', true),
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
				const config = await createProvisioningSettings(settings)
				logger.info('new provisioning config saved', { config })
				this.configs.unshift(config)
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
		async updateAllowNewMailAccounts(checked) {
			await updateAllowNewMailAccounts(checked)
		},
	},
}
</script>
<style lang="scss" scoped>
.app-description {
		margin-bottom: 24px;
	}
.config-button {
	display: inline-block;
	margin-inline: 4px;
}
</style>
