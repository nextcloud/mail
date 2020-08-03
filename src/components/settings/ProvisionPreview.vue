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
	<div class="provision-preview">
		<b>
			<span v-if="data.uid">uid={{ data.uid }}</span>
			<span v-if="data.email">email={{ email }}</span>
		</b>
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
	},
}
</script>

<style lang="scss" scoped>
.provision-preview {
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
}
</style>
