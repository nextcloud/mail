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
