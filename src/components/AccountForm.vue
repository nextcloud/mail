<template>
	<form id="account-form" @submit.prevent="onSubmit">
		<Tabs
			:options="{useUrlFragment: false, defaultTabHash: settingsPage ? 'manual' : 'auto'}"
			cache-lifetime="0"
			@changed="onModeChanged">
			<Tab id="auto" key="auto" :name="t('mail', 'Auto')">
				<label for="auto-name">{{ t('mail', 'Name') }}</label>
				<input
					id="auto-name"
					v-model="autoConfig.accountName"
					type="text"
					:placeholder="t('mail', 'Name')"
					:disabled="loading"
					autofocus>
				<label for="auto-address" class="account-form__label--required">{{ t('mail', 'Mail address') }}</label>
				<input
					id="auto-address"
					v-model.lazy="autoConfig.emailAddress"
					type="email"
					:placeholder="t('mail', 'name@example.org')"
					:disabled="loading"
					required
					@blur="isValidEmail(autoConfig.emailAddress)">
				<p v-if="!isValidEmail(autoConfig.emailAddress)" class="account-form--error">
					{{ t('mail', 'Please enter an email of the format name@example.com') }}
				</p>
				<label for="auto-password" class="account-form__label--required">{{ t('mail', 'Password') }}</label>
				<input
					id="auto-password"
					v-model="autoConfig.password"
					type="password"
					:placeholder="t('mail', 'Password')"
					:disabled="loading"
					required>
			</Tab>
			<Tab id="manual" key="manual" :name="t('mail', 'Manual')">
				<label for="man-name">{{ t('mail', 'Name') }}</label>
				<input
					id="man-name"
					v-model="manualConfig.accountName"
					type="text"
					:placeholder="t('mail', 'Name')"
					:disabled="loading">
				<label for="man-address" class="account-form__label--required">{{ t('mail', 'Mail address') }}</label>
				<input
					id="man-address"
					v-model.lazy="manualConfig.emailAddress"
					type="email"
					:placeholder="t('mail', 'name@example.org')"
					:disabled="loading"
					required
					@blur="isValidEmail(manualConfig.emailAddress)">
				<p v-if="!isValidEmail(manualConfig.emailAddress)" class="account-form--error">
					{{ t('mail', 'Please enter an email of the format name@example.com') }}
				</p>

				<h3>{{ t('mail', 'IMAP Settings') }}</h3>
				<label for="man-imap-host" class="account-form__label--required">{{ t('mail', 'IMAP Host') }}</label>
				<input
					id="man-imap-host"
					v-model="manualConfig.imapHost"
					type="text"
					:placeholder="t('mail', 'IMAP Host')"
					:disabled="loading"
					required>
				<h4 class="account-form__heading--required">
					{{ t('mail', 'IMAP Security') }}
				</h4>
				<div class="flex-row">
					<input
						id="man-imap-sec-none"
						v-model="manualConfig.imapSslMode"
						type="radio"
						name="man-imap-sec"
						:disabled="loading"
						value="none"
						@change="onImapSslModeChange">
					<label
						class="button"
						for="man-imap-sec-none"
						:class="{primary: manualConfig.imapSslMode === 'none'}">{{ t('mail', 'None') }}</label>
					<input
						id="man-imap-sec-ssl"
						v-model="manualConfig.imapSslMode"
						type="radio"
						name="man-imap-sec"
						:disabled="loading"
						value="ssl"
						@change="onImapSslModeChange">
					<label
						class="button"
						for="man-imap-sec-ssl"
						:class="{primary: manualConfig.imapSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
					<input
						id="man-imap-sec-tls"
						v-model="manualConfig.imapSslMode"
						type="radio"
						name="man-imap-sec"
						:disabled="loading"
						value="tls"
						@change="onImapSslModeChange">
					<label
						class="button"
						for="man-imap-sec-tls"
						:class="{primary: manualConfig.imapSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
				</div>
				<label for="man-imap-port" class="account-form__label--required">{{ t('mail', 'IMAP Port') }}</label>
				<input
					id="man-imap-port"
					v-model="manualConfig.imapPort"
					type="number"
					:placeholder="t('mail', 'IMAP Port')"
					:disabled="loading"
					required>
				<label for="man-imap-user" class="account-form__label--required">{{ t('mail', 'IMAP User') }}</label>
				<input
					id="man-imap-user"
					v-model="manualConfig.imapUser"
					type="text"
					:placeholder="t('mail', 'IMAP User')"
					:disabled="loading"
					required>
				<label for="man-imap-password" class="account-form__label--required">{{ t('mail', 'IMAP Password') }}</label>
				<input
					id="man-imap-password"
					v-model="manualConfig.imapPassword"
					type="password"
					:placeholder="t('mail', 'IMAP Password')"
					:disabled="loading"
					required>

				<h3>{{ t('mail', 'SMTP Settings') }}</h3>
				<label for="man-smtp-host" class="account-form__label--required">{{ t('mail', 'SMTP Host') }}</label>
				<input
					id="man-smtp-host"
					ref="smtpHost"
					v-model="manualConfig.smtpHost"
					type="text"
					name="smtp-host"
					:placeholder="t('mail', 'SMTP Host')"
					:disabled="loading"
					required>
				<h4 class="account-form__heading--required">
					{{ t('mail', 'SMTP Security') }}
				</h4>
				<div class="flex-row">
					<input
						id="man-smtp-sec-none"
						v-model="manualConfig.smtpSslMode"
						type="radio"
						name="man-smtp-sec"
						:disabled="loading"
						value="none"
						@change="onSmtpSslModeChange">
					<label
						class="button"
						for="man-smtp-sec-none"
						:class="{primary: manualConfig.smtpSslMode === 'none'}">{{ t('mail', 'None') }}</label>
					<input
						id="man-smtp-sec-ssl"
						v-model="manualConfig.smtpSslMode"
						type="radio"
						name="man-smtp-sec"
						:disabled="loading"
						value="ssl"
						@change="onSmtpSslModeChange">
					<label
						class="button"
						for="man-smtp-sec-ssl"
						:class="{primary: manualConfig.smtpSslMode === 'ssl'}">{{ t('mail', 'SSL/TLS') }}</label>
					<input
						id="man-smtp-sec-tls"
						v-model="manualConfig.smtpSslMode"
						type="radio"
						name="man-smtp-sec"
						:disabled="loading"
						value="tls"
						@change="onSmtpSslModeChange">
					<label
						class="button"
						for="man-smtp-sec-tls"
						:class="{primary: manualConfig.smtpSslMode === 'tls'}">{{ t('mail', 'STARTTLS') }}</label>
				</div>
				<label for="man-smtp-port" class="account-form__label--required">{{ t('mail', 'SMTP Port') }}</label>
				<input
					id="man-smtp-port"
					v-model="manualConfig.smtpPort"
					type="number"
					:placeholder="t('mail', 'SMTP Port')"
					:disabled="loading"
					required>
				<label for="man-smtp-user" class="account-form__label--required">{{ t('mail', 'SMTP User') }}</label>
				<input
					id="man-smtp-user"
					v-model="manualConfig.smtpUser"
					type="text"
					:placeholder="t('mail', 'SMTP User')"
					:disabled="loading"
					required>
				<label for="man-smtp-password" class="account-form__label--required">{{ t('mail', 'SMTP Password') }}</label>
				<input
					id="man-smtp-password"
					v-model="manualConfig.smtpPassword"
					type="password"
					:placeholder="t('mail', 'SMTP Password')"
					:disabled="loading"
					required>
			</Tab>
		</Tabs>
		<div class="account-form__submit-buttons">
			<button v-if="mode === 'auto'"
				class="primary account-form__submit-button"
				type="submit"
				:disabled="isDisabledAuto"
				@click.prevent="onSubmit">
				<span v-if="loading" class="icon-loading-small account-form__submit-button__spinner" />
				{{ submitButtonText }}
			</button>

			<button v-else-if="mode === 'manual'"
				class="primary account-form__submit-button"
				type="submit"
				:disabled="isDisabledManual"
				@click.prevent="onSubmit">
				<span v-if="loading" class="icon-loading-small account-form__submit-button__spinner" />
				{{ submitButtonText }}
			</button>
		</div>
		<div class="account-form--feedback">
			<slot name="feedback" />
		</div>
	</form>
</template>

<script>
import { Tab, Tabs } from 'vue-tabs-component'

import logger from '../logger'

export default {
	name: 'AccountForm',
	components: {
		Tab,
		Tabs,
	},
	props: {
		displayName: {
			type: String,
			default: '',
		},
		email: {
			type: String,
			default: '',
		},
		save: {
			type: Function,
			required: true,
		},
		account: {
			type: Object,
			required: false,
			default: () => undefined,
		},
	},
	data() {
		const fromAccountOr = (prop, def) => {
			if (this.account !== undefined) {
				return this.account[prop]
			} else {
				return def
			}
		}

		return {
			loading: false,
			mode: 'auto',
			autoConfig: {
				accountName: this.displayName,
				emailAddress: this.email,
				password: '',
			},
			manualConfig: {
				accountName: '',
				emailAddress: '',
				imapHost: fromAccountOr('imapHost', ''),
				imapPort: fromAccountOr('imapPort', 993),
				imapSslMode: fromAccountOr('imapSslMode', 'ssl'),
				imapUser: fromAccountOr('imapUser', ''),
				imapPassword: '',
				smtpHost: fromAccountOr('smtpHost', ''),
				smtpPort: fromAccountOr('smtpPort', 587),
				smtpSslMode: fromAccountOr('smtpSslMode', 'tls'),
				smtpUser: fromAccountOr('smtpUser', ''),
				smtpPassword: '',
			},
		}
	},
	computed: {
		settingsPage() {
			return this.account !== undefined
		},

		isDisabledAuto() {
			switch (this.mode === 'auto') {
			case !this.autoConfig.emailAddress || !this.isValidEmail(this.autoConfig.emailAddress) || !this.autoConfig.password :
				return true
			}
			return this.loading
		},

		isDisabledManual() {
			switch (this.mode === 'manual') {
			case !this.manualConfig.emailAddress || !this.isValidEmail(this.manualConfig.emailAddress)
			|| !this.manualConfig.imapHost || !this.manualConfig.imapPort
			|| !this.manualConfig.imapUser || !this.manualConfig.imapPassword
			|| !this.manualConfig.smtpHost || !this.manualConfig.smtpPort
			|| !this.manualConfig.smtpUser || !this.manualConfig.smtpPassword:
				return true
			}
			return this.loading
		},

		submitButtonText() {
			if (this.loading) {
				return t('mail', 'Connecting')
			}
			return this.account ? t('mail', 'Save') : t('mail', 'Connect')
		},
	},
	methods: {
		onModeChanged(e) {
			this.mode = e.tab.id

			if (this.mode === 'manual') {
				if (this.manualConfig.accountName === '') {
					this.manualConfig.accountName = this.autoConfig.accountName
				}
				if (this.manualConfig.emailAddress === '') {
					this.manualConfig.emailAddress = this.autoConfig.emailAddress
				}

				// IMAP
				if (this.manualConfig.imapUser === '') {
					this.manualConfig.imapUser = this.autoConfig.emailAddress
				}
				if (this.manualConfig.imapPassword === '') {
					this.manualConfig.imapPassword = this.autoConfig.password
				}

				// SMTP
				if (this.manualConfig.smtpUser === '') {
					this.manualConfig.smtpUser = this.autoConfig.emailAddress
				}
				if (this.manualConfig.smtpPassword === '') {
					this.manualConfig.smtpPassword = this.autoConfig.password
				}
			}
		},
		onImapSslModeChange() {
			switch (this.manualConfig.imapSslMode) {
			case 'none':
			case 'tls':
				this.manualConfig.imapPort = 143
				break
			case 'ssl':
				this.manualConfig.imapPort = 993
				break
			}
		},
		onSmtpSslModeChange() {
			switch (this.manualConfig.smtpSslMode) {
			case 'none':
			case 'tls':
				this.manualConfig.smtpPort = 587
				break
			case 'ssl':
				this.manualConfig.smtpPort = 465
				break
			}
		},
		saveChanges() {
			if (this.mode === 'auto') {
				return this.save({
					autoDetect: true,
					...this.autoConfig,
				})
			} else {
				// Removing additional whitespaces from manual configuration hosts
				// In order to avoid issues when copy pasting imap & smtp hosts from providers documentations,
				// which may have whitespaces.
				return this.save({
					autoDetect: false,
					...this.manualConfig,
					imapHost: this.manualConfig.imapHost.trim(),
					smtpHost: this.manualConfig.smtpHost.trim(),
				})
			}
		},
		onSubmit(event) {
			if (!this.isDisabledManual || !this.isDisabledAuto) {
				console.debug('account form submitted', { event })
				this.loading = true
				this.saveChanges()
					.catch((error) => logger.error('could not save account details', { error }))
					.then(() => (this.loading = false))
			}
		},
		isValidEmail(email) {
			const regExpEmail = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
			return regExpEmail.test(email)
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep .tabs-component-tabs {
	display: flex;
}

::v-deep .tabs-component-tab {
	flex-grow: 1;
	text-align: center;
	color: var(--color-text-lighter);
	margin-bottom: 10px;
}

::v-deep .tabs-component-tab.is-active {
	border-bottom: 1px solid black;
	font-weight: bold;
}

.tabs-component-panels {
	padding-top: 20px;
}

.tabs-component-panels label {
	text-align: left;
	width: 100%;
	display: inline-block;
}

.tabs-component-panels input,
.tabs-component-panels select {
	margin-bottom: 10px;
}
</style>

<style scoped>
h4 {
	text-align: left;
}

.flex-row {
	display: flex;
}

label.button {
	display: inline-block;
	text-align: center;
	flex-grow: 1;
}
label.primary {
	color: var(--color-main-background);
}
input.primary {
	color: var(--color-main-background);
}

input[type='radio'] {
	display: none;
}

input[type='radio'][disabled] + label {
	cursor: default;
	opacity: 0.5;
}
.account-form__label--required:after {
	content:" *";
}
.account-form__heading--required:after {
	content:" *";
}
.account-form__submit-buttons {
	display: flex;
	justify-content: center;
	margin-top: 5px;
}
.account-form__submit-button {
	display: flex;
	align-items: center;
}
.account-form__submit-button__spinner {
	margin: 0 10px 0 0;
	height: auto;
	width: auto;
}
.account-form--feedback {
	color: var(--color-text-maxcontrast);
	margin-top: 5px;
	text-align: center;
}
.account-form--error {
	text-align: left;
	font-size: 14px;
}
</style>
