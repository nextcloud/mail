<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { JmapServerConfig, JmapSslMode } from '../models/accountServerConfig.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'

const props = withDefaults(defineProps<{
	disabled?: boolean
	modelValue: JmapServerConfig
	passwordOptional?: boolean
}>(), {
	disabled: false,
	passwordOptional: false,
})

const emit = defineEmits<{
	change: []
	'update:modelValue': [config: JmapServerConfig]
}>()

const JMAP_PLAIN_PORT = 80
const JMAP_SSL_PORT = 443

const passwordLabel = computed(() => props.passwordOptional
	? t('mail', 'Leave blank to not change password')
	: t('mail', 'JMAP Password'))

function update<K extends keyof JmapServerConfig>(key: K, value: JmapServerConfig[K]): void {
	emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function onSslModeChange(sslMode: JmapSslMode): void {
	emit('update:modelValue', {
		...props.modelValue,
		sslMode,
		port: sslMode === 'yes' ? JMAP_SSL_PORT : JMAP_PLAIN_PORT,
	})
	emit('change')
}
</script>

<template>
	<div class="account-form-jmap">
		<h4>{{ t('mail', 'JMAP Settings') }}</h4>
		<NcInputField
			id="man-jmap-host"
			:modelValue="modelValue.host"
			:label="t('mail', 'JMAP Host')"
			type="text"
			:placeholder="t('mail', 'JMAP Host')"
			:disabled="disabled"
			required
			@update:modelValue="update('host', $event)"
			@change="emit('change')" />
		<h4 class="account-form-jmap__heading--required">
			{{ t('mail', 'JMAP Security') }}
		</h4>
		<div class="account-form-jmap__security">
			<NcCheckboxRadioSwitch
				id="man-jmap-sec-none"
				:buttonVariant="true"
				:modelValue="modelValue.sslMode"
				type="radio"
				name="man-jmap-sec"
				:disabled="disabled"
				value="no"
				buttonVariantGrouped="horizontal"
				@update:modelValue="onSslModeChange">
				{{ t('mail', 'None') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch
				id="man-jmap-sec-ssl"
				:buttonVariant="true"
				:modelValue="modelValue.sslMode"
				type="radio"
				name="man-jmap-sec"
				:disabled="disabled"
				value="yes"
				buttonVariantGrouped="horizontal"
				@update:modelValue="onSslModeChange">
				{{ t('mail', 'SSL/TLS') }}
			</NcCheckboxRadioSwitch>
		</div>
		<NcInputField
			id="man-jmap-port"
			:modelValue="modelValue.port"
			:label="t('mail', 'JMAP Port')"
			type="number"
			:placeholder="t('mail', 'JMAP Port')"
			:disabled="disabled"
			required
			@update:modelValue="update('port', $event)"
			@change="emit('change')" />
		<NcInputField
			id="man-jmap-path"
			:modelValue="modelValue.path"
			:label="t('mail', 'JMAP Session Path')"
			type="text"
			placeholder="/.well-known/jmap"
			:disabled="disabled"
			@update:modelValue="update('path', $event)"
			@change="emit('change')" />
		<NcInputField
			id="man-jmap-user"
			:modelValue="modelValue.user"
			:label="t('mail', 'JMAP User')"
			type="text"
			:placeholder="t('mail', 'JMAP User')"
			:disabled="disabled"
			required
			@update:modelValue="update('user', $event)"
			@change="emit('change')" />
		<NcPasswordField
			id="man-jmap-password"
			:modelValue="modelValue.password"
			:label="passwordLabel"
			:disabled="disabled"
			:required="!passwordOptional"
			@update:modelValue="update('password', $event)"
			@change="emit('change')" />
	</div>
</template>

<style scoped>
h4 {
	font-size: 17px;
	text-align: start;
}

.account-form-jmap__heading--required:after {
	content: " *";
}

.account-form-jmap__security {
	display: flex;
}
</style>
