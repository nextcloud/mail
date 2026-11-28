<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { JmapServerConfig, JmapSslMode } from '../models/accountServerConfig.ts'

import { t } from '@nextcloud/l10n'
import { computed, ref, watch } from 'vue'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'

type JmapServerLocation = Pick<JmapServerConfig, 'host' | 'path' | 'port' | 'sslMode'>

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

function defaultPort(sslMode: JmapSslMode): number {
	return sslMode === 'yes' ? JMAP_SSL_PORT : JMAP_PLAIN_PORT
}

const EMPTY_LOCATION: JmapServerLocation = { host: '', path: '', port: JMAP_SSL_PORT, sslMode: 'yes' }

function formatServerUrl(location: JmapServerLocation): string {
	if (location.host === '') {
		return ''
	}
	const port = Number(location.port) === defaultPort(location.sslMode) ? '' : `:${location.port}`
	return `${location.sslMode === 'yes' ? 'https' : 'http'}://${location.host}${port}${location.path}`
}

function parseServerUrl(value: string): JmapServerLocation | null {
	const input = value.trim()
	let url: URL
	try {
		url = new URL(input.includes('://') ? input : `https://${input}`)
	} catch {
		return null
	}
	if (url.protocol !== 'https:' && url.protocol !== 'http:') {
		return null
	}

	const sslMode: JmapSslMode = url.protocol === 'https:' ? 'yes' : 'no'
	return {
		host: url.hostname,
		path: url.pathname === '/' ? '' : url.pathname,
		port: url.port === '' ? defaultPort(sslMode) : Number(url.port),
		sslMode,
	}
}

const serverUrl = ref(formatServerUrl(props.modelValue))

const serverUrlInvalid = computed(() => serverUrl.value.trim() !== '' && parseServerUrl(serverUrl.value) === null)

watch(() => formatServerUrl(props.modelValue), (url) => {
	if (formatServerUrl(parseServerUrl(serverUrl.value) ?? EMPTY_LOCATION) !== url) {
		serverUrl.value = url
	}
})

function update<K extends keyof JmapServerConfig>(key: K, value: JmapServerConfig[K]): void {
	emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function onServerUrlInput(value: string | number): void {
	serverUrl.value = String(value)
	emit('update:modelValue', { ...props.modelValue, ...(parseServerUrl(serverUrl.value) ?? EMPTY_LOCATION) })
}
</script>

<template>
	<div class="account-form-jmap">
		<h4>{{ t('mail', 'JMAP Settings') }}</h4>
		<NcInputField
			id="man-jmap-url"
			:modelValue="serverUrl"
			:label="t('mail', 'JMAP Server')"
			type="text"
			placeholder="mail.example.com"
			:disabled="disabled"
			:error="serverUrlInvalid"
			:helperText="serverUrlInvalid
				? t('mail', 'Enter a server name or an http(s) address')
				: t('mail', 'For example mail.example.com or https://mail.example.com/.well-known/jmap')"
			required
			@update:modelValue="onServerUrlInput"
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
</style>
