<template>
	<div id="account-form">
		<tabs :options="{ useUrlFragment: false, defaultTabHash: 'auto' }"
			  @changed="onModeChanged">
			<tab :name="t('mail', 'Auto')"
				 id="auto"
				 key="auto">
				<label for="auto-name">
					{{ t('mail', 'Name') }}
				</label>
				<input type="text"
					   id="auto-name"
					   :placeholder="t('mail', 'Name')"
					   v-model="autoConfig.accountName"
					   :disabled="loading"
					   autofocus/>
				<label for="auto-address">
					{{ t('mail', 'Mail Address') }}
				</label>
				<input type="email"
					   id="auto-address"
					   :placeholder="t('mail', 'Mail Address')"
					   v-model="autoConfig.emailAddress"
					   :disabled="loading"
					   required/>
				<label for="auto-password">
					{{ t('mail', 'Password') }}
				</label>
				<input type="password"
					   id="auto-password"
					   :placeholder="t('mail', 'Password')"
					   v-model="autoConfig.password"
					   :disabled="loading"
					   required/>
			</tab>
			<tab :name="t('mail', 'Manual')"
				 id="manual"
				 key="manual">
				<label for="man-name">
					{{ t('mail', 'Name') }}
				</label>
				<input type="text"
					   id="man-name"
					   :placeholder="t('mail', 'Name')"
					   v-model="manualConfig.accountName"
					   :disabled="loading"
					   autofocus/>
				<label for="man-address">
					{{ t('mail', 'Mail Address') }}
				</label>
				<input type="email"
					   id="man-address"
					   :placeholder="t('mail', 'Mail Address')"
					   v-model="manualConfig.emailAddress"
					   :disabled="loading"
					   required/>

				<h3>{{ t('mail', 'IMAP Settings') }}</h3>
				<label for="man-imap-host">
					{{ t('mail', 'IMAP Host') }}
				</label>
				<input type="text"
					   id="man-imap-host"
					   :placeholder="t('mail', 'IMAP Host')"
					   v-model="manualConfig.imapHost"
					   :disabled="loading"
					   required/>
				<h4>{{ t('mail', 'IMAP Security') }}</h4>
				<div class="flex-row">
					<input type="radio"
						   id="man-imap-sec-none"
						   name="man-imap-sec"
						   v-model="manualConfig.imapSslMode"
						   :disabled="loading"
						   @change="onImapSslModeChange"
						   value="none">
					<label class="button"
						   for="man-imap-sec-none"
						   :class="{primary: manualConfig.imapSslMode === 'none' }">
						{{ t('mail', 'None') }}
					</label>
					<input type="radio"
						   id="man-imap-sec-ssl"
						   name="man-imap-sec"
						   v-model="manualConfig.imapSslMode"
						   :disabled="loading"
						   @change="onImapSslModeChange"
						   value="ssl">
					<label class="button"
						   for="man-imap-sec-ssl"
						   :class="{primary: manualConfig.imapSslMode === 'ssl' }">
						{{ t('mail', 'SSL/TLS') }}
					</label>
					<input type="radio"
						   id="man-imap-sec-tls"
						   name="man-imap-sec"
						   v-model="manualConfig.imapSslMode"
						   :disabled="loading"
						   @change="onImapSslModeChange"
						   value="tls">
					<label class="button"
						   for="man-imap-sec-tls"
						   :class="{primary: manualConfig.imapSslMode === 'tls' }">
						{{ t('mail', 'STARTTLS') }}
					</label>
				</div>
				<label for="man-imap-port">
					{{ t('mail', 'IMAP Port') }}
				</label>
				<input type="number"
					   id="man-imap-port"
					   :placeholder="t('mail', 'IMAP Port')"
					   v-model="manualConfig.imapPort"
					   :disabled="loading"
					   required/>
				<label for="man-imap-user">
					{{ t('mail', 'IMAP User') }}
				</label>
				<input type="text"
					   id="man-imap-user"
					   :placeholder="t('mail', 'IMAP User')"
					   v-model="manualConfig.imapUser"
					   :disabled="loading"
					   required/>
				<label for="man-imap-password">
					{{ t('mail', 'IMAP Password') }}
				</label>
				<input type="password"
					   id="man-imap-password"
					   :placeholder="t('mail', 'IMAP Password')"
					   v-model="manualConfig.imapPassword"
					   :disabled="loading"
					   required/>

				<h3>{{ t('mail', 'SMTP Settings') }}</h3>
				<input type="text"
					   ref="smtpHost"
					   name="smtp-host"
					   :placeholder="t('mail', 'SMTP Host')"
					   v-model="manualConfig.smtpHost"
					   :disabled="loading"
					   required/>
				<h4>{{ t('mail', 'SMTP Security') }}</h4>
				<div class="flex-row">
					<input type="radio"
						   id="man-smtp-sec-none"
						   name="man-smtp-sec"
						   v-model="manualConfig.smtpSslMode"
						   :disabled="loading"
						   @change="onSmtpSslModeChange"
						   value="none">
					<label class="button"
						   for="man-smtp-sec-none"
						   :class="{primary: manualConfig.smtpSslMode === 'none' }">
						{{ t('mail', 'None') }}
					</label>
					<input type="radio"
						   id="man-smtp-sec-ssl"
						   name="man-smtp-sec"
						   v-model="manualConfig.smtpSslMode"
						   :disabled="loading"
						   @change="onSmtpSslModeChange"
						   value="ssl">
					<label class="button"
						   for="man-smtp-sec-ssl"
						   :class="{primary: manualConfig.smtpSslMode === 'ssl' }">
						{{ t('mail', 'SSL/TLS') }}
					</label>
					<input type="radio"
						   id="man-smtp-sec-tls"
						   name="man-smtp-sec"
						   v-model="manualConfig.smtpSslMode"
						   :disabled="loading"
						   @change="onSmtpSslModeChange"
						   value="tls">
					<label class="button"
						   for="man-smtp-sec-tls"
						   :class="{primary: manualConfig.smtpSslMode === 'tls' }">
						{{ t('mail', 'STARTTLS') }}
					</label>
				</div>
				<label for="man-smtp-port">
					{{ t('mail', 'SMTP Port') }}
				</label>
				<input type="number"
					   id="man-smtp-port"
					   :placeholder="t('mail', 'SMTP Port')"
					   v-model="manualConfig.smtpPort"
					   :disabled="loading"
					   required/>
				<label for="man-smtp-user">
					{{ t('mail', 'SMTP User') }}
				</label>
				<input type="text"
					   id="man-smtp-user"
					   :placeholder="t('mail', 'SMTP User')"
					   v-model="manualConfig.smtpUser"
					   :disabled="loading"
					   required/>
				<label for="man-smtp-password">
					{{ t('mail', 'SMTP Password') }}
				</label>
				<input type="password"
					   id="man-smtp-password"
					   :placeholder="t('mail', 'SMTP Password')"
					   v-model="manualConfig.smtpPassword"
					   :disabled="loading"
					   required/>
			</tab>
		</tabs>
		<input type="submit"
			   class="primary"
			   v-on:click="onSubmit"
			   :disabled="loading"
			   :value="t('mail', 'Connect')"/>
	</div>
</template>

<script>
	import {Tab, Tabs} from 'vue-tabs-component'

	export default {
		name: 'AccountForm',
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
			}
		},
		components: {
			Tab,
			Tabs,
		},
		data () {
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
					accountName: '',
					autoDetect: true,
					imapHost: '',
					imapPort: 993,
					imapSslMode: 'ssl',
					imapUser: '',
					imapPassword: '',
					smtpHost: '',
					smtpPort: 587,
					smtpSslMode: 'tls',
					smtpUser: '',
					smtpPassword: '',
				}
			};
		},
		methods: {
			onModeChanged (e) {
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
			onImapSslModeChange: function () {
				switch (this.manualConfig.imapSslMode) {
					case 'none':
					case 'tls':
						this.manualConfig.imapPort = 143;
						break;
					case 'ssl':
						this.manualConfig.imapPort = 993;
						break;
				}
			},
			onSmtpSslModeChange: function () {
				switch (this.manualConfig.smtpSslMode) {
					case 'none':
					case 'tls':
						this.manualConfig.smtpPort = 587;
						break;
					case 'ssl':
						this.manualConfig.smtpPort = 465;
						break;
				}
			},
			saveChanges () {
				if (this.mode === 'auto') {
					return this.save({
						mode: this.mode,
						...this.autoConfig,
					})
				} else {
					return this.save({
						mode: this.mode,
						...this.manualConfig,
					})
				}
			},
			onSubmit: function () {
				this.loading = true

				this.saveChanges()
					.catch(console.error.bind(this))
					.then(() => this.loading = false)
			}
		}
	};
</script>

<style>
	.tabs-component-tabs {
		display: flex;
	}

	.tabs-component-tab {
		flex-grow: 1;
		color: var(--color-text-lighter);
		font-weight: bold;
	}

	.tabs-component-tab.is-active {
		border-bottom: 1px solid black;
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

	input[type="radio"] {
		display: none;
	}

	input[type=radio][disabled] + label {
		cursor: default;
		opacity: 0.5;
	}
</style>
