<template>
	<form id="sieve-form">
		<p>
			<input
				id="sieve-disabled"
				v-model="sieveConfig.sieveEnabled"
				type="radio"
				class="radio"
				name="sieve-active"
				:value="false">
			<label
				:class="{primary: !sieveConfig.sieveEnabled}"
				for="sieve-disabled">
				{{ t('mail', 'Disabled') }}
			</label>
			<input
				id="sieve-enabled"
				v-model="sieveConfig.sieveEnabled"
				type="radio"
				class="radio"
				name="sieve-active"
				:value="true">
			<label
				:class="{primary: sieveConfig.sieveEnabled}"
				for="sieve-enabled">
				{{ t('mail', 'Enabled') }}
			</label>
		</p>
		<template v-if="sieveConfig.sieveEnabled">
			<label for="sieve-host">{{ t('mail', 'Sieve Host') }}</label>
			<input
				id="sieve-host"
				v-model="sieveConfig.sieveHost"
				type="text"
				required>
			<h4>{{ t('mail', 'Sieve Security') }}</h4>
			<div class="flex-row">
				<input
					id="sieve-sec-none"
					v-model="sieveConfig.sieveSslMode"
					type="radio"
					name="sieve-sec"
					value="none">
				<label
					class="button"
					for="sieve-sec-none"
					:class="{primary: sieveConfig.sieveSslMode === 'none'}">{{
						t('mail', 'None')
					}}</label>
				<input
					id="sieve-sec-ssl"
					v-model="sieveConfig.sieveSslMode"
					type="radio"
					name="sieve-sec"
					value="ssl">
				<label
					class="button"
					for="sieve-sec-ssl"
					:class="{primary: sieveConfig.sieveSslMode === 'ssl'}">
					{{ t('mail', 'SSL/TLS') }}
				</label>
				<input
					id="sieve-sec-tls"
					v-model="sieveConfig.sieveSslMode"
					type="radio"
					name="sieve-sec"
					value="tls">
				<label
					class="button"
					for="sieve-sec-tls"
					:class="{primary: sieveConfig.sieveSslMode === 'tls'}">
					{{ t('mail', 'STARTTLS') }}
				</label>
			</div>
			<label for="sieve-port">{{ t('mail', 'Sieve Port') }}</label>
			<input
				id="sieve-port"
				v-model="sieveConfig.sievePort"
				type="text"
				required>
			<h4>{{ t('mail', 'Sieve Credentials') }}</h4>
			<p>
				<input
					id="sieve-credentials-imap"
					v-model="useImapCredentials"
					type="radio"
					class="radio"
					:value="true">
				<label
					:class="{primary: useImapCredentials}"
					for="sieve-credentials-imap">
					{{ t('mail', 'IMAP Credentials') }}
				</label>
				<input
					id="sieve-credentials-custom"
					v-model="useImapCredentials"
					type="radio"
					class="radio"
					:value="false">
				<label
					:class="{primary: !useImapCredentials}"
					for="sieve-credentials-custom">
					{{ t('mail', 'Custom') }}
				</label>
			</p>
			<template v-if="!useImapCredentials">
				<label for="sieve-user">{{ t('mail', 'Sieve User') }}</label>
				<input
					id="sieve-user"
					v-model="sieveConfig.sieveUser"
					type="text"
					required>
				<label for="sieve-password">{{
					t('mail', 'Sieve Password')
				}}</label>
				<input
					id="sieve-password"
					v-model="sieveConfig.sievePassword"
					type="password"
					required>
			</template>
		</template>
		<slot name="feedback" />
		<p v-if="errorMessage">
			{{ t('mail', 'Oh Snap!') }}
			{{ errorMessage }}
		</p>
		<ButtonVue type="primary"
			:disabled="loading"
			@click.prevent="onSubmit">
			{{ t('mail', 'Save sieve settings') }}
		</ButtonVue>
	</form>
</template>

<script>
import { NcButton as ButtonVue } from '@nextcloud/vue'
export default {
	name: 'SieveAccountForm',
	components: {
		ButtonVue,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			sieveConfig: {
				sieveEnabled: this.account.sieveEnabled,
				sieveHost: this.account.sieveHost || this.account.imapHost,
				sievePort: this.account.sievePort || 4190,
				sieveUser: this.account.sieveUser || '',
				sievePassword: '',
				sieveSslMode: this.account.sieveSslMode || 'tls',
			},
			loading: false,
			useImapCredentials: !this.account.sieveUser,
			errorMessage: '',
		}
	},
	methods: {
		async onSubmit() {
			this.loading = true
			this.errorMessage = ''

			// empty user and password => use imap credentials
			if (this.sieveConfig.sieveUser === '' && this.sieveConfig.sievePassword === '') {
				this.useImapCredentials = true
			}

			// clear user and password if imap credentials are used
			if (this.useImapCredentials) {
				this.sieveConfig.sieveUser = ''
				this.sieveConfig.sievePassword = ''
			}

			try {
				await this.$store.dispatch('updateSieveAccount', {
					account: this.account,
					data: this.sieveConfig,
				})
				if (this.sieveConfig.sieveEnabled) {
					await this.$store.dispatch('fetchActiveSieveScript', {
						accountId: this.account.id,
					})
				}
			} catch (error) {
				this.errorMessage = error.message
			}

			this.loading = false
		},
	},
}
</script>

<style scoped>
form {
	width: 250px
}

label {
	display: inline-block;
}

input {
	width: 100%;
}

.flex-row {
	display: flex;
}

label.button {
	text-align: center;
	flex-grow: 1;
}

label.error {
	color: red;
}

input[type='radio'] {
	display: none;
}
</style>
