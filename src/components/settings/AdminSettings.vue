<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<SettingsSection
		:name="t('mail', 'Mail app')"
		:description="t('mail', 'The mail app allows users to read mails on their IMAP accounts.')">
		<p>
			{{
				t(
					'mail',
					'Here you can find instance-wide settings. User specific settings are found in the app itself (bottom-left corner).',
				)
			}}
		</p>
		<div class="app-description">
			<h3>
				{{
					t(
						'mail',
						'Account provisioning',
					)
				}}
			</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'A provisioning configuration will provision all accounts with a matching email address.',
						)
					}}
					{{
						t(
							'mail',
							'Using the wildcard (*) in the provisioning domain field will create a configuration that applies to all users, provided they do not match another configuration.',
						)
					}}
					<br>
					{{
						t(
							'mail',
							'The provisioning mechanism will prioritise specific domain configurations over the wildcard domain configuration.',
						)
					}}
					{{
						t(
							'mail',
							'Should a new matching configuration be found after the user was already provisioned with another configuration, the new configuration will take precedence and the user will be reprovisioned with the configuration.',
						)
					}}
					<br>
					{{
						t(
							'mail',
							'There can only be one configuration per domain and only one wildcard domain configuration.',
						)
					}}
					<br>
					{{
						t(
							'mail',
							'These settings can be used in conjunction with each other.',
						)
					}}
					<br>
					{{
						t(
							'mail',
							'If you only want to provision one domain for all users, use the wildcard (*).',
						)
					}}
					<br>
					{{
						t(
							'mail',
							'This setting only makes most sense if you use the same user back-end for your Nextcloud and mail server of your organization.',
						)
					}}
				</p>
			</article>
		</div>
		<h3>
			{{
				t(
					'mail',
					'Provisioning Configurations',
				)
			}}
		</h3>
		<p>
			<ButtonVue
				class="config-button"
				:aria-label="t('mail', 'Add new config')"
				@click="addNew = true">
				<template #icon>
					<IconAdd :size="20" />
				</template>
				{{ t('mail', 'Add new config') }}
			</ButtonVue>
			<ButtonVue
				class="config-button"
				:aria-label="t('mail', 'Provision all accounts')"
				@click="provisionAll">
				<template #icon>
					<IconSettings :size="20" />
				</template>
				{{ t('mail', 'Provision all accounts') }}
			</ButtonVue>
			<ProvisioningSettings
				v-if="addNew"
				:key="formKey"
				:setting="preview"
				:submit="saveNewSettings"
				:delete-button="false" />
			<ProvisioningSettings
				v-for="setting in configs"
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
						v-model="allowNewMailAccounts"
						type="switch"
						@update:checked="updateAllowNewMailAccounts">
						{{ t('mail', 'Allow additional Mail accounts from User Settings') }}
					</NcCheckboxRadioSwitch>
				</p>
			</article>
		</div>
		<div
			v-if="isLlmSummaryConfigured"
			class="app-description">
			<h3>{{ t('mail', 'Enable text processing through LLMs') }}</h3>
			<article>
				<p>
					{{ t('mail', 'The Mail app can process user data with the help of the configured large language model and provide assistance features like thread summaries, smart replies and event agendas.') }}
				</p>
				<p>
					<NcCheckboxRadioSwitch
						v-model="isLlmEnabled"
						type="switch"
						@update:checked="updateLlmEnabled">
						{{ t('mail', 'Enable LLM processing') }}
					</NcCheckboxRadioSwitch>
				</p>
			</article>
		</div>
		<div
			v-if="isLlmSummaryConfigured && isLlmEnabled"
			class="app-description">
			<h3>{{ t('mail', 'Custom LLM prompts') }}</h3>
			<article>
				<p>
					{{ t('mail', 'Customize the prompts used for AI-powered features. Use {body} as a placeholder for the email content and {language} for the user language code where applicable.', { body: '{body}', language: '{language}' }) }}
				</p>
				<div class="prompt-fields">
					<div class="prompt-field">
						<h4>{{ t('mail', 'Message summary prompt') }}</h4>
						<p class="prompt-description">
							{{ t('mail', 'Placeholders: {body} for the email body, {language} for the user language code.', { body: '{body}', language: '{language}' }) }}
						</p>
						<NcTextArea
							v-model="promptValues.llm_prompt_summarize"
							:label="t('mail', 'Message summary prompt')"
							resize="vertical"
							rows="4" />
						<ButtonVue
							v-if="promptValues.llm_prompt_summarize !== defaultPrompts.llm_prompt_summarize"
							variant="tertiary"
							@click="resetPrompt('llm_prompt_summarize')">
							{{ t('mail', 'Reset to default') }}
						</ButtonVue>
					</div>
					<div class="prompt-field">
						<h4>{{ t('mail', 'Smart reply prompt') }}</h4>
						<p class="prompt-description">
							{{ t('mail', 'Placeholders: {body} for the email body.', { body: '{body}' }) }}
						</p>
						<NcTextArea
							v-model="promptValues.llm_prompt_smart_reply"
							:label="t('mail', 'Smart reply prompt')"
							resize="vertical"
							rows="4" />
						<ButtonVue
							v-if="promptValues.llm_prompt_smart_reply !== defaultPrompts.llm_prompt_smart_reply"
							variant="tertiary"
							@click="resetPrompt('llm_prompt_smart_reply')">
							{{ t('mail', 'Reset to default') }}
						</ButtonVue>
					</div>
					<div class="prompt-field">
						<h4>{{ t('mail', 'Follow-up detection prompt') }}</h4>
						<p class="prompt-description">
							{{ t('mail', 'Placeholders: {body} for the email body.', { body: '{body}' }) }}
						</p>
						<NcTextArea
							v-model="promptValues.llm_prompt_follow_up"
							:label="t('mail', 'Follow-up detection prompt')"
							resize="vertical"
							rows="4" />
						<ButtonVue
							v-if="promptValues.llm_prompt_follow_up !== defaultPrompts.llm_prompt_follow_up"
							variant="tertiary"
							@click="resetPrompt('llm_prompt_follow_up')">
							{{ t('mail', 'Reset to default') }}
						</ButtonVue>
					</div>
					<div class="prompt-field">
						<h4>{{ t('mail', 'Translation detection prompt') }}</h4>
						<p class="prompt-description">
							{{ t('mail', 'Placeholders: {body} for the email body, {language} for the user language code.', { body: '{body}', language: '{language}' }) }}
						</p>
						<NcTextArea
							v-model="promptValues.llm_prompt_translation"
							:label="t('mail', 'Translation detection prompt')"
							resize="vertical"
							rows="4" />
						<ButtonVue
							v-if="promptValues.llm_prompt_translation !== defaultPrompts.llm_prompt_translation"
							variant="tertiary"
							@click="resetPrompt('llm_prompt_translation')">
							{{ t('mail', 'Reset to default') }}
						</ButtonVue>
					</div>
					<div class="prompt-field">
						<h4>{{ t('mail', 'Event data generation prompt') }}</h4>
						<p class="prompt-description">
							{{ t('mail', 'The email thread content will be appended to this prompt.') }}
						</p>
						<NcTextArea
							v-model="promptValues.llm_prompt_event_data"
							:label="t('mail', 'Event data generation prompt')"
							resize="vertical"
							rows="4" />
						<ButtonVue
							v-if="promptValues.llm_prompt_event_data !== defaultPrompts.llm_prompt_event_data"
							variant="tertiary"
							@click="resetPrompt('llm_prompt_event_data')">
							{{ t('mail', 'Reset to default') }}
						</ButtonVue>
					</div>
					<ButtonVue
						variant="primary"
						:disabled="savingPrompts"
						@click="saveCustomPrompts">
						{{ t('mail', 'Save custom prompts') }}
					</ButtonVue>
				</div>
			</article>
		</div>
		<div class="app-description">
			<h3>{{ t('mail', 'Enable classification by importance by default') }}</h3>
			<article>
				<p>
					{{ t('mail', 'The Mail app can classify incoming emails by importance using machine learning. This feature is enabled by default but can be disabled by default here. Individual users will still be able to toggle the feature for their accounts.') }}
				</p>
				<p>
					<NcCheckboxRadioSwitch
						type="switch"
						:model-value="isImportanceClassificationEnabledByDefault"
						@update:checked="setImportanceClassificationEnabledByDefault">
						{{ t('mail', 'Enable classification of important mails by default') }}
					</NcCheckboxRadioSwitch>
				</p>
			</article>
		</div>
		<div class="app-description">
			<h3>
				{{
					t(
						'mail',
						'Anti Spam Service',
					)
				}}
			</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'You can set up an anti spam service email address here.',
						)
					}}
					<br>
					{{
						t(
							'mail',
							'Any email that is marked as spam will be sent to the anti spam service.',
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
						'Gmail integration',
					)
				}}
			</h3>
			<article>
				<p>
					{{
						t(
							'mail',
							'Gmail allows users to access their email via IMAP. For security reasons this access is only possible with an OAuth 2.0 connection or Google accounts that use two-factor authentication and app passwords.',
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
							},
						)
					}}
				</p>
			</article>
			<GmailAdminOauthSettings :client-id="googleOauthClientId" />
		</div>
		<div class="app-description">
			<h3>
				{{ t('mail', 'Microsoft integration') }}
			</h3>
			<article>
				<p>
					{{ t('mail', 'Microsoft requires you to access your emails via IMAP using OAuth 2.0 authentication. To do this, you need to register an app with Microsoft Entra ID, formerly known as Microsoft Azure Active Directory.') }}
				</p>
				<p>
					{{ t('mail', 'Redirect URI') }}: <code>{{ microsoftOauthRedirectUrl }}</code>
				</p>
				<a :href="microsoftOauthDocs" target="_blank" rel="noopener noreferrer">{{ t('mail', 'For more details, please click here to open our documentation.') }}</a>
			</article>
			<MicrosoftAdminOauthSettings :tenant-id="microsoftOauthTenantId" :client-id="microsoftOauthClientId" />
		</div>
		<div class="app-description">
			<h3>{{ t('mail', 'User Interface Preference Defaults') }}</h3>
			<article>
				<p>
					{{ t('mail', 'These settings are used to pre-configure the user interface preferences they can be overridden by the user in the mail settings') }}
				</p>
			</article>
			<br>
			<article>
				<p>
					{{ t('mail', 'Message View Mode') }}
				</p>
				<p>
					<NcCheckboxRadioSwitch
						v-model="layoutMessageView"
						type="radio"
						name="message_view_mode_radio"
						value="threaded"
						@update:checked="setLayoutMessageView('threaded')">
						{{ t('mail', 'Show all messages in thread') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						v-model="layoutMessageView"
						type="radio"
						name="message_view_mode_radio"
						value="singleton"
						@update:checked="setLayoutMessageView('singleton')">
						{{ t('mail', 'Show only the selected message') }}
					</NcCheckboxRadioSwitch>
				</p>
			</article>
		</div>
	</SettingsSection>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import ButtonVue from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import SettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import IconSettings from 'vue-material-design-icons/CogOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import AntiSpamSettings from './AntiSpamSettings.vue'
import GmailAdminOauthSettings from './GmailAdminOauthSettings.vue'
import MicrosoftAdminOauthSettings from './MicrosoftAdminOauthSettings.vue'
import ProvisioningSettings from './ProvisioningSettings.vue'
import logger from '../../logger.js'
import {
	createProvisioningSettings,
	disableProvisioning,
	provisionAll,
	setImportanceClassificationEnabledByDefault,
	setLayoutMessageView,
	setLlmCustomPrompts,
	updateAllowNewMailAccounts,
	updateEnabledSmartReply,
	updateLlmEnabled,
	updateProvisioningSettings,
} from '../../service/SettingsService.js'

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
		ButtonVue,
		IconAdd,
		IconSettings,
		NcCheckboxRadioSwitch,
		NcTextArea,
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
			microsoftOauthDocs: loadState('mail', 'microsoft_oauth_docs'),
			microsoftOauthRedirectUrl,
			preview: {
				provisioningDomain: '',
				emailTemplate: '',
				imapHost: 'mx.domain.com',
				imapPort: 993,
				imapUser: '%USERID%@domain.com',
				imapSslMode: 'ssl',
				smtpHost: 'mx.domain.com',
				smtpPort: 587,
				smtpUser: '%USERID%@domain.com',
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
			isLlmSummaryConfigured: loadState('mail', 'enabled_llm_summary_backend'),
			isLlmEnabled: loadState('mail', 'llm_processing', true),
			isLlmFreePromptConfigured: loadState('mail', 'enabled_llm_free_prompt_backend'),
			layoutMessageView: loadState('mail', 'layout_message_view'),
			isImportanceClassificationEnabledByDefault: loadState('mail', 'importance_classification_default', true),

			defaultPrompts: loadState('mail', 'llm_default_prompts', {}),
			promptValues: (() => {
				const custom = loadState('mail', 'llm_custom_prompts', {
					llm_prompt_summarize: '',
					llm_prompt_smart_reply: '',
					llm_prompt_follow_up: '',
					llm_prompt_translation: '',
					llm_prompt_event_data: '',
				})
				const defaults = loadState('mail', 'llm_default_prompts', {})
				const values = {}
				for (const key of Object.keys(custom)) {
					values[key] = custom[key] || defaults[key] || ''
				}
				return values
			})(),

			savingPrompts: false,
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
			const deleted = this.configs.find((c) => c.id === id)
			try {
				await disableProvisioning(id)
				logger.info('Deprovisioned successfully')
				this.configs = this.configs.filter((c) => c.id !== id)
				showSuccess(t('mail', 'Successfully deleted and deprovisioned accounts for "{domain}"', { domain: deleted.provisioningDomain }))
			} catch (error) {
				showError(t('mail', 'Error when deleting and deprovisioning accounts for "{domain}"', { domain: deleted.provisioningDomain }))
				logger.error('Could not delete provisioning config', { error })
			}
		},

		async updateAllowNewMailAccounts(checked) {
			await updateAllowNewMailAccounts(checked)
		},

		async updateLlmEnabled(checked) {
			await updateLlmEnabled(checked)
		},

		async updateEnabledSmartReply(checked) {
			await updateEnabledSmartReply(checked)
		},

		async setLayoutMessageView(value) {
			await setLayoutMessageView(value)
		},

		async setImportanceClassificationEnabledByDefault(enabledByDefault) {
			try {
				await setImportanceClassificationEnabledByDefault(enabledByDefault)
				this.isImportanceClassificationEnabledByDefault = !this.isImportanceClassificationEnabledByDefault
			} catch (error) {
				showError(t('mail', 'Could not save default classification setting'))
				logger.error('Could not save default classification setting', { error })
			}
		},

		resetPrompt(key) {
			this.promptValues[key] = this.defaultPrompts[key] || ''
		},

		async saveCustomPrompts() {
			this.savingPrompts = true
			try {
				const prompts = {}
				for (const key of Object.keys(this.promptValues)) {
					prompts[key] = this.promptValues[key] === this.defaultPrompts[key] ? '' : this.promptValues[key]
				}
				await setLlmCustomPrompts(prompts)
				showSuccess(t('mail', 'Custom prompts saved'))
			} catch (error) {
				showError(t('mail', 'Could not save custom prompts'))
				logger.error('Could not save custom prompts', { error })
			} finally {
				this.savingPrompts = false
			}
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

.prompt-fields {
	display: flex;
	flex-direction: column;
	gap: 16px;
	margin-top: 12px;
}

.prompt-field {
	display: flex;
	flex-direction: column;
	gap: 4px;

	h4 {
		font-weight: bold;
		margin: 0;
	}

	:deep(.textarea) {
		height: auto;
	}

	:deep(.textarea__main-wrapper) {
		height: auto;
	}
}

.prompt-description {
	color: var(--color-text-maxcontrast);
	font-size: small;
	margin: 0;
}
</style>
