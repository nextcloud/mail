<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form id="sieve-form">
		<p>
			{{ t('mail', 'Sieve is a powerful language for writing filters for your mailbox. You can manage the sieve scripts in Mail if your email service supports it. Sieve is also required to use Autoresponder and Filters.') }}
		</p>
		<p>
			<NcCheckboxRadioSwitch :checked.sync="sieveConfig.sieveEnabled">
				{{ t('mail', 'Enable sieve filter') }}
			</NcCheckboxRadioSwitch>
		</p>
		<div v-if="sieveConfig.sieveEnabled">
			<NcTextField :label="t('mail', 'Sieve host')" :value.sync="sieveConfig.sieveHost" />
			<h4>{{ t('mail', 'Sieve security') }}</h4>
			<div class="flex-row">
				<ButtonVue :pressed="sieveConfig.sieveSslMode === 'none'"
					@click="updateSslMode('none')">
					{{ t('mail', 'None') }}
				</ButtonVue>
				<ButtonVue :pressed="sieveConfig.sieveSslMode === 'ssl'"
					@click="updateSslMode('ssl')">
					{{ t('mail', 'SSL/TLS') }}
				</ButtonVue>
				<ButtonVue :pressed="sieveConfig.sieveSslMode === 'tls'"
					@click="updateSslMode('tls')">
					{{ t('mail', 'STARTTLS') }}
				</ButtonVue>
			</div>
			<NcTextField :label="t('mail', 'Sieve Port')" :value.sync="sieveConfig.sievePort" />
			<h4>{{ t('mail', 'Sieve credentials') }}</h4>
			<div class="flex-row">
				<ButtonVue :pressed="useImapCredentials"
					@click="updateCredentials(true)">
					{{ t('mail', 'IMAP credentials') }}
				</ButtonVue>
				<ButtonVue :pressed="!useImapCredentials"
					@click="updateCredentials(false)">
					{{ t('mail', 'Custom') }}
				</ButtonVue>
			</div>
			<p v-if="!useImapCredentials" class="custom">
				<NcTextField :label="t('mail', 'Sieve User')" :value.sync="sieveConfig.sieveUser" />
				<NcPasswordField :label="t('mail', 'Sieve Password')" :value.sync="sieveConfig.sievePassword" />
			</p>
		</div>
		<slot name="feedback" />
		<p v-if="errorMessage">
			{{ t('mail', 'Oh snap!') }}
			{{ errorMessage }}
		</p>
		<ButtonVue type="primary"
			:disabled="loading"
			:aria-label="t('mail', 'Save sieve settings')"
			@click.prevent="onSubmit">
			{{ t('mail', 'Save sieve settings') }}
		</ButtonVue>
	</form>
</template>

<script>
import { NcButton as ButtonVue, NcTextField, NcPasswordField, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'SieveAccountForm',
	components: {
		ButtonVue,
		NcTextField,
		NcPasswordField,
		NcCheckboxRadioSwitch,
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
	computed: {
		...mapStores(useMainStore),
	},
	methods: {
		updateSslMode(value) {
			this.sieveConfig.sieveSslMode = value
		},
		updateCredentials(value) {
			this.useImapCredentials = value
		},
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
				await this.mainStore.updateSieveAccount({
					account: this.account,
					data: this.sieveConfig,
				})
				if (this.sieveConfig.sieveEnabled) {
					await this.mainStore.fetchActiveSieveScript({
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
	width: 300px
}

label {
	display: inline-block;
}

input {
	width: 100%;
}

.flex-row {
	display: flex;
	gap: var(--default-grid-baseline);
	margin-bottom: calc(var(--default-grid-baseline) * 4);
}

.custom {
	margin-bottom: calc(var(--default-grid-baseline) * 4);
}

input[type='radio'] {
	display: none;
}
</style>
