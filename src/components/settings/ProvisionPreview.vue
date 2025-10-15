<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="provision-preview">
		<b>
			<span v-if="data.uid">uid={{ data.uid }}</span>
			<span v-if="data.email">email={{ email }}</span>
		</b>
		<br>
		{{ t('mail', 'Domain Match: {provisioningDomain}', {provisioningDomain}) }}
		<br>
		{{ t('mail', 'Email: {email}', {email}) }}<br>
		{{
			t('mail', 'IMAP: {user} on {host}:{port} ({ssl} encryption)', {
				user: imapUser,
				host: imapHost,
				port: imapPort,
				ssl: imapSslMode,
			})
		}}<br>
		{{
			t('mail', 'SMTP: {user} on {host}:{port} ({ssl} encryption)', {
				user: smtpUser,
				host: smtpHost,
				port: smtpPort,
				ssl: smtpSslMode,
			})
		}}<br>
		<span v-if="sieveEnabled">
			{{
				t('mail', 'Sieve: {user} on {host}:{port} ({ssl} encryption)', {
					user: sieveUser,
					host: sieveHost,
					port: sievePort,
					ssl: sieveSslMode,
				})
			}}<br>
		</span>
	</div>
</template>

<script>
export default {
	name: 'ProvisionPreview',
	props: {
		data: {
			type: Object,
			required: true,
		},
		templates: {
			type: Object,
			required: true,
		},
	},
	computed: {
		email() {
			return this.templates.email.replace('%USERID%', this.data.uid).replace('%EMAIL%', this.data.email)
		},
		provisioningDomain() {
			return this.templates.provisioningDomain
		},
		imapHost() {
			return this.templates.imapHost
		},
		imapPort() {
			return this.templates.imapPort
		},
		imapSslMode() {
			return this.templates.imapSslMode
		},
		imapUser() {
			return this.templates.imapUser.replace('%USERID%', this.data.uid).replace('%EMAIL%', this.data.email)
		},
		smtpHost() {
			return this.templates.smtpHost
		},
		smtpPort() {
			return this.templates.smtpPort
		},
		smtpSslMode() {
			return this.templates.smtpSslMode
		},
		smtpUser() {
			return this.templates.smtpUser.replace('%USERID%', this.data.uid).replace('%EMAIL%', this.data.email)
		},
		sieveEnabled() {
			return this.templates.sieveEnabled
		},
		sieveHost() {
			return this.templates.sieveHost
		},
		sievePort() {
			return this.templates.sievePort
		},
		sieveSslMode() {
			return this.templates.sieveSslMode
		},
		sieveUser() {
			return this.templates.sieveUser.replace('%USERID%', this.data.uid).replace('%EMAIL%', this.data.email)
		},
	},
}
</script>

<style lang="scss" scoped>
.provision-preview {
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
}
</style>
