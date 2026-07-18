<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form class="oidc-provider-form" @submit.prevent="submitForm">
		<h4 class="oidc-provider-form__title">
			{{ setting.id
				? t('mail', 'Provider for "{emailDomain}"', { emailDomain: emailDomain || '…' })
				: t('mail', 'New OIDC provider') }}
		</h4>

		<NcInputField
			v-model="name"
			:label="t('mail', 'Display name')"
			:disabled="loading"
			required />
		<NcInputField
			v-model="emailDomain"
			:label="t('mail', 'Email domain')"
			:disabled="loading"
			placeholder="example.com"
			required />

		<h5>{{ t('mail', 'IMAP') }}</h5>
		<div class="host-port">
			<NcInputField
				v-model="imapHost"
				class="host"
				:label="t('mail', 'Host')"
				:disabled="loading"
				required />
			<NcInputField
				v-model="imapPort"
				class="port"
				type="number"
				:label="t('mail', 'Port')"
				:disabled="loading"
				required />
		</div>
		<div class="ssl-row">
			<NcCheckboxRadioSwitch
				v-for="mode in sslModes"
				:key="'imap-' + mode.value"
				:button-variant="true"
				:model-value="imapSslMode"
				type="radio"
				name="oidc-imap-ssl"
				button-variant-grouped="horizontal"
				:value="mode.value"
				:disabled="loading"
				@update:checked="imapSslMode = $event">
				{{ mode.label }}
			</NcCheckboxRadioSwitch>
		</div>

		<h5>{{ t('mail', 'SMTP') }}</h5>
		<div class="host-port">
			<NcInputField
				v-model="smtpHost"
				class="host"
				:label="t('mail', 'Host')"
				:disabled="loading"
				required />
			<NcInputField
				v-model="smtpPort"
				class="port"
				type="number"
				:label="t('mail', 'Port')"
				:disabled="loading"
				required />
		</div>
		<div class="ssl-row">
			<NcCheckboxRadioSwitch
				v-for="mode in sslModes"
				:key="'smtp-' + mode.value"
				:button-variant="true"
				:model-value="smtpSslMode"
				type="radio"
				name="oidc-smtp-ssl"
				button-variant-grouped="horizontal"
				:value="mode.value"
				:disabled="loading"
				@update:checked="smtpSslMode = $event">
				{{ mode.label }}
			</NcCheckboxRadioSwitch>
		</div>

		<h5>{{ t('mail', 'OpenID Connect') }}</h5>
		<NcCheckboxRadioSwitch v-model="manualEndpoints" :disabled="loading">
			{{ t('mail', 'Manually define endpoints') }}
		</NcCheckboxRadioSwitch>
		<NcInputField
			v-show="!manualEndpoints"
			v-model="discoveryUrl"
			type="url"
			:label="t('mail', 'Discovery endpoint (.well-known/openid-configuration)')"
			:disabled="loading"
			placeholder="https://idp.example.com/.well-known/openid-configuration" />
		<NcInputField
			v-show="manualEndpoints"
			v-model="authorizationEndpoint"
			type="url"
			:label="t('mail', 'Authorization endpoint')"
			:disabled="loading"
			placeholder="https://idp.example.com/authorize" />
		<NcInputField
			v-show="manualEndpoints"
			v-model="tokenEndpoint"
			type="url"
			:label="t('mail', 'Token endpoint')"
			:disabled="loading"
			placeholder="https://idp.example.com/token" />
		<NcInputField
			v-model="clientId"
			:label="t('mail', 'Client ID')"
			:disabled="loading"
			required />
		<NcPasswordField
			v-model="clientSecret"
			:label="t('mail', 'Client secret')"
			:disabled="loading"
			autocomplete="new-password" />
		<NcInputField
			v-model="scope"
			:label="t('mail', 'Scopes')"
			:disabled="loading" />
		<p class="redirect-hint">
			{{ t('mail', 'Redirect URL to register with the provider:') }}
			<code>{{ redirectUrl }}</code>
		</p>

		<div class="actions">
			<ButtonVue type="secondary" native-type="submit" :disabled="loading">
				<template #icon>
					<IconUpload :size="20" />
				</template>
				{{ t('mail', 'Save') }}
			</ButtonVue>
			<ButtonVue
				v-if="setting.id"
				type="secondary"
				:disabled="loading"
				@click="deleteProvider">
				<template #icon>
					<IconDelete :size="20" />
				</template>
				{{ t('mail', 'Delete') }}
			</ButtonVue>
		</div>
	</form>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import ButtonVue from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconUpload from 'vue-material-design-icons/TrayArrowUp.vue'
import logger from '../../logger.js'

const CLIENT_SECRET_PLACEHOLDER = '********'

export default {
	name: 'OidcProviderForm',
	components: {
		ButtonVue,
		NcCheckboxRadioSwitch,
		NcInputField,
		NcPasswordField,
		IconUpload,
		IconDelete,
	},

	props: {
		setting: {
			type: Object,
			required: true,
		},

		redirectUrl: {
			type: String,
			default: '',
		},

		submit: {
			type: Function,
			required: true,
		},

		remove: {
			type: Function,
			required: false,
			default: undefined,
		},
	},

	data() {
		return {
			loading: false,
			name: this.setting.name || '',
			emailDomain: this.setting.emailDomain || '',
			imapHost: this.setting.imapHost || '',
			imapPort: this.setting.imapPort || 993,
			imapSslMode: this.setting.imapSslMode || 'ssl',
			smtpHost: this.setting.smtpHost || '',
			smtpPort: this.setting.smtpPort || 587,
			smtpSslMode: this.setting.smtpSslMode || 'tls',
			clientId: this.setting.clientId || '',
			clientSecret: this.setting.clientSecret ? CLIENT_SECRET_PLACEHOLDER : '',
			manualEndpoints: this.setting.manualEndpoints || false,
			discoveryUrl: this.setting.discoveryUrl || '',
			authorizationEndpoint: this.setting.authorizationEndpoint || '',
			tokenEndpoint: this.setting.tokenEndpoint || '',
			scope: this.setting.scope || 'openid email offline_access',
		}
	},

	computed: {
		sslModes() {
			return [
				{ value: 'none', label: t('mail', 'None') },
				{ value: 'ssl', label: t('mail', 'SSL/TLS') },
				{ value: 'tls', label: t('mail', 'STARTTLS') },
			]
		},
	},

	methods: {
		t,

		async submitForm() {
			this.loading = true
			try {
				await this.submit({
					id: this.setting.id || null,
					name: this.name,
					emailDomain: this.emailDomain,
					imapHost: this.imapHost,
					imapPort: Number(this.imapPort),
					imapSslMode: this.imapSslMode,
					smtpHost: this.smtpHost,
					smtpPort: Number(this.smtpPort),
					smtpSslMode: this.smtpSslMode,
					clientId: this.clientId,
					clientSecret: this.clientSecret,
					manualEndpoints: this.manualEndpoints,
					discoveryUrl: this.discoveryUrl,
					authorizationEndpoint: this.authorizationEndpoint,
					tokenEndpoint: this.tokenEndpoint,
					scope: this.scope,
				})
			} catch (error) {
				logger.error('Could not save OIDC provider', { error })
			} finally {
				this.loading = false
			}
		},

		async deleteProvider() {
			this.loading = true
			try {
				await this.remove(this.setting.id)
			} catch (error) {
				logger.error('Could not delete OIDC provider', { error })
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.oidc-provider-form {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	max-width: 480px;
	margin-block-end: calc(var(--default-grid-baseline) * 6);

	&__title {
		font-weight: bold;
	}

	h5 {
		margin-block-start: calc(var(--default-grid-baseline) * 2);
		font-weight: bold;
	}
}

.host-port {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: flex-end;

	.host {
		flex-grow: 1;
	}

	.port {
		width: 8rem;
	}
}

.ssl-row {
	display: flex;
}

.actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-block-start: calc(var(--default-grid-baseline) * 2);
}

.redirect-hint {
	color: var(--color-text-maxcontrast);

	code {
		word-break: break-all;
	}
}
</style>
