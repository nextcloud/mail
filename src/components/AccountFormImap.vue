<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ImapServerConfig, ImapSslMode } from '../models/accountServerConfig.ts'

import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'

const props = withDefaults(defineProps<{
	disabled?: boolean
	modelValue: ImapServerConfig
	oauth?: boolean
	passwordOptional?: boolean
}>(), {
	disabled: false,
	passwordOptional: false,
	oauth: false,
})

const emit = defineEmits<{
	change: []
	'update:modelValue': [config: ImapServerConfig]
}>()

const IMAP_PLAIN_PORT = 143
const IMAP_SSL_PORT = 993
const SMTP_PLAIN_PORT = 587
const SMTP_SSL_PORT = 465

const securityModes: { label: string, value: ImapSslMode }[] = [
	{ value: 'none', label: t('mail', 'None') },
	{ value: 'ssl', label: t('mail', 'SSL/TLS') },
	{ value: 'tls', label: t('mail', 'STARTTLS') },
]

function passwordLabel(fallback: string): string {
	return props.passwordOptional ? t('mail', 'Leave blank to not change password') : fallback
}

function update<K extends keyof ImapServerConfig>(key: K, value: ImapServerConfig[K]): void {
	emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function onImapSslModeChange(imapSslMode: ImapSslMode): void {
	emit('update:modelValue', {
		...props.modelValue,
		imapSslMode,
		imapPort: imapSslMode === 'ssl' ? IMAP_SSL_PORT : IMAP_PLAIN_PORT,
	})
	emit('change')
}

function onSmtpSslModeChange(smtpSslMode: ImapSslMode): void {
	emit('update:modelValue', {
		...props.modelValue,
		smtpSslMode,
		smtpPort: smtpSslMode === 'ssl' ? SMTP_SSL_PORT : SMTP_PLAIN_PORT,
	})
	emit('change')
}

function syncCredentials(): void {
	if (props.modelValue.syncSmtpCredentials) {
		emit('update:modelValue', {
			...props.modelValue,
			smtpHost: props.modelValue.imapHost,
			smtpUser: props.oauth ? props.modelValue.smtpUser : props.modelValue.imapUser,
			smtpPassword: props.modelValue.imapPassword,
		})
	}
	emit('change')
}

function unsyncCredentials(): void {
	update('syncSmtpCredentials', false)
	emit('change')
}
</script>

<template>
	<div class="account-form-imap">
		<h4>{{ t('mail', 'IMAP Settings') }}</h4>
		<NcInputField
			id="man-imap-host"
			:modelValue="modelValue.imapHost"
			:label="t('mail', 'IMAP Host')"
			type="text"
			:placeholder="t('mail', 'IMAP Host')"
			:disabled="disabled"
			required
			@update:modelValue="update('imapHost', $event)"
			@change="syncCredentials" />
		<h4 class="account-form-imap__heading--required">
			{{ t('mail', 'IMAP Security') }}
		</h4>
		<div class="account-form-imap__security">
			<NcCheckboxRadioSwitch
				v-for="mode in securityModes"
				:id="`man-imap-sec-${mode.value}`"
				:key="mode.value"
				:buttonVariant="true"
				:modelValue="modelValue.imapSslMode"
				type="radio"
				name="man-imap-sec"
				:disabled="disabled"
				:value="mode.value"
				buttonVariantGrouped="horizontal"
				@update:modelValue="onImapSslModeChange">
				{{ mode.label }}
			</NcCheckboxRadioSwitch>
		</div>
		<NcInputField
			id="man-imap-port"
			:modelValue="modelValue.imapPort"
			:label="t('mail', 'IMAP Port')"
			type="number"
			:placeholder="t('mail', 'IMAP Port')"
			:disabled="disabled"
			required
			@update:modelValue="update('imapPort', $event)"
			@change="emit('change')" />
		<NcInputField
			id="man-imap-user"
			:modelValue="modelValue.imapUser"
			:label="t('mail', 'IMAP User')"
			type="text"
			:placeholder="t('mail', 'IMAP User')"
			:disabled="disabled"
			required
			@update:modelValue="update('imapUser', $event)"
			@change="syncCredentials" />
		<NcPasswordField
			v-if="!oauth"
			id="man-imap-password"
			:modelValue="modelValue.imapPassword"
			:label="passwordLabel(t('mail', 'IMAP Password'))"
			:disabled="disabled"
			required
			@update:modelValue="update('imapPassword', $event)"
			@change="syncCredentials" />

		<h4>{{ t('mail', 'SMTP Settings') }}</h4>
		<NcInputField
			id="man-smtp-host"
			:modelValue="modelValue.smtpHost"
			:label="t('mail', 'SMTP Host')"
			type="text"
			name="smtp-host"
			:placeholder="t('mail', 'SMTP Host')"
			:disabled="disabled"
			required
			@update:modelValue="update('smtpHost', $event)"
			@change="unsyncCredentials" />
		<h4 class="account-form-imap__heading--required">
			{{ t('mail', 'SMTP Security') }}
		</h4>
		<div class="account-form-imap__security">
			<NcCheckboxRadioSwitch
				v-for="mode in securityModes"
				:id="`man-smtp-sec-${mode.value}`"
				:key="mode.value"
				:buttonVariant="true"
				:modelValue="modelValue.smtpSslMode"
				type="radio"
				name="man-smtp-sec"
				:disabled="disabled"
				:value="mode.value"
				buttonVariantGrouped="horizontal"
				@update:modelValue="onSmtpSslModeChange">
				{{ mode.label }}
			</NcCheckboxRadioSwitch>
		</div>
		<NcInputField
			id="man-smtp-port"
			:modelValue="modelValue.smtpPort"
			:label="t('mail', 'SMTP Port')"
			type="number"
			:placeholder="t('mail', 'SMTP Port')"
			:disabled="disabled"
			required
			@update:modelValue="update('smtpPort', $event)"
			@change="emit('change')" />
		<NcInputField
			id="man-smtp-user"
			:modelValue="modelValue.smtpUser"
			:label="t('mail', 'SMTP User')"
			:helperText="oauth ? t('mail', 'The account you sign in with. For a shared mailbox, enter your own email address.') : ''"
			type="text"
			:placeholder="t('mail', 'SMTP User')"
			:disabled="disabled"
			required
			@update:modelValue="update('smtpUser', $event)"
			@change="unsyncCredentials" />
		<NcPasswordField
			v-if="!oauth"
			id="man-smtp-password"
			:modelValue="modelValue.smtpPassword"
			:label="passwordLabel(t('mail', 'SMTP Password'))"
			:disabled="disabled"
			required
			@update:modelValue="update('smtpPassword', $event)"
			@change="unsyncCredentials" />
	</div>
</template>

<style scoped>
h4 {
	font-size: 17px;
	text-align: start;
}

.account-form-imap__heading--required:after {
	content: " *";
}

.account-form-imap__security {
	display: flex;
}
</style>
