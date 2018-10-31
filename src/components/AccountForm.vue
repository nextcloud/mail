<template>
	<div id="account-form">
		<tabs :options="{ useUrlFragment: false }">
			<tab :name="t('mail', 'Auto')"
				 key="auto">
				<label for="auto-name">
					{{ t('mail', 'Name') }}
				</label>
				<input type="text"
					   id="auto-name"
					   :placeholder="t('mail', 'Name')"
					   v-model="autoConfig.accountName"
					   autofocus/>
				<label for="auto-address">
					{{ t('mail', 'Mail Address') }}
				</label>
				<input type="email"
					   id="auto-address"
					   :placeholder="t('mail', 'Mail Address')"
					   v-model="autoConfig.emailAddress"
					   required/>
				<label for="auto-password">
					{{ t('mail', 'Password') }}
				</label>
				<input type="password"
					   id="auto-password"
					   :placeholder="t('mail', 'Password')"
					   v-model="autoConfig.password"
					   required/>
			</tab>
			<tab :name="t('mail', 'Manual')"
				 key="manual">
				<label for="man-name">
					{{ t('mail', 'Name') }}
				</label>
				<input type="text"
					   id="man-name"
					   :placeholder="t('mail', 'Name')"
					   v-model="manualConfig.accountName"
					   autofocus/>
				<label for="man-address">
					{{ t('mail', 'Mail Address') }}
				</label>
				<input type="email"
					   id="man-address"
					   :placeholder="t('mail', 'Mail Address')"
					   v-model="manualConfig.emailAddress"
					   required/>

				<h3>{{ t('mail', 'IMAP Settings') }}</h3>
				<label for="man-imap-host">
					{{ t('mail', 'IMAP Host') }}
				</label>
				<input type="text"
					   id="man-imap-host"
					   :placeholder="t('mail', 'IMAP Host')"
					   v-model="manualConfig.imapHost"/>
				<h4>{{ t('mail', 'IMAP Security') }}</h4>
				<div class="flex-row">
					<label class="button"
						   :class="{primary: manualConfig.imapSslMode === 'none' }">
						<input type="radio"
							   name="man-imap-sec"
							   v-model="manualConfig.imapSslMode"
							   value="none">
						{{ t('mail', 'None') }}
					</label>
					<label class="button"
						   :class="{primary: manualConfig.imapSslMode === 'ssl' }">
						<input type="radio"
							   name="man-imap-sec"
							   v-model="manualConfig.imapSslMode"
							   value="ssl">
						{{ t('mail', 'SSL/TLS') }}
					</label>
					<label class="button"
						   :class="{primary: manualConfig.imapSslMode === 'tls' }">
						<input type="radio"
							   name="man-imap-sec"
							   v-model="manualConfig.imapSslMode"
							   value="tls">
						{{ t('mail', 'STARTTLS') }}
					</label>
				</div>
				<input type="number"
					   ref="imapPort"
					   name="imap-port"
					   :placeholder="t('mail', 'IMAP Port')"
					   v-model="manualConfig.imapPort"/>
				<input type="text"
					   ref="imapUser"
					   name="imap-user"
					   :placeholder="t('mail', 'IMAP User')"
					   v-model="manualConfig.imapUser"/>
				<input type="password"
					   ref="imapPassword"
					   name="imap-password"
					   :placeholder="t('mail', 'IMAP Password')"
					   v-model="manualConfig.imapPassword"
					   required/>

				<h3>{{ t('mail', 'SMTP Settings') }}</h3>
				<input type="text"
					   ref="smtpHost"
					   name="smtp-host"
					   :placeholder="t('mail', 'SMTP Host')"
					   v-model="manualConfig.smtpHost"/>
				<div class="flex-row">
					<label class="button"
						   :class="{primary: manualConfig.smtpSslMode === 'none' }">
						<input type="radio"
							   name="man-smtp-sec"
							   v-model="manualConfig.smtpSslMode"
							   value="none">
						{{ t('mail', 'None') }}
					</label>
					<label class="button"
						   :class="{primary: manualConfig.smtpSslMode === 'ssl' }">
						<input type="radio"
							   name="man-smtp-sec"
							   v-model="manualConfig.smtpSslMode"
							   value="ssl">
						{{ t('mail', 'SSL/TLS') }}
					</label>
					<label class="button"
						   :class="{primary: manualConfig.smtpSslMode === 'tls' }">
						<input type="radio"
							   name="man-smtp-sec"
							   v-model="manualConfig.smtpSslMode"
							   value="tls">
						{{ t('mail', 'STARTTLS') }}
					</label>
				</div>
				<input type="number"
					   ref="smtpPort"
					   name="smtp-port"
					   :placeholder="t('mail', 'SMTP Port')"
					   v-model="manualConfig.smtpPort"/>
				<input type="text"
					   ref="smtpUser"
					   name="smtp-user"
					   :placeholder="t('mail', 'SMTP User')"
					   v-model="manualConfig.smtpUser"/>
				<input type="password"
					   ref="smtpPassword"
					   name="smtp-password"
					   :placeholder="t('mail', 'SMTP Password')"
					   v-model="manualConfig.smtpPassword"
					   required/>
			</tab>
		</tabs>
		<input type="submit"
			   ref="submitButton"
			   class="primary"
			   :value="t('mail', 'Connect')"/>
	</div>
</template>

<script>
	import {Tabs, Tab} from 'vue-tabs-component'

	export default {
		name: 'AccountForm',
		props: {
			settingsPage: Boolean
		},
		components: {
			Tab,
			Tabs,
		},
		data () {
			return {
				firstToggle: true,
				autoConfig: {
					accountName: $('#user-displayname').text() || '',
					emailAddress: $('#user-email').text() || '',
					password: '',
				},
				manualConfig: {
					accountName: $('#user-displayname').text() || '',
					emailAddress: $('#user-email').text() || '',
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
		mounted () {
			if (this.settingsPage) {
				$(this.$refs.emptyContent).hide();
				$(this.$refs.submitButton).val(t('mail', 'Save'));
			}

			if (this.config.autoDetect) {
				$(this.$refs.mailPassword).show();
				$(this.$refs.manualInputs).hide();
			} else {
				$(this.$refs.mailPassword).hide();
			}
		},
		methods: {
			toggleManualMode: function () {
				this.config.autoDetect = !this.config.autoDetect;

				$(this.$refs.manualInputs).slideToggle();
				this.$refs.imapHost.focus();

				if (!this.config.autoDetect) {
					if (this.firstToggle) {
						// Manual mode opened for the first time
						// -> copy email, password for imap&smtp
						const email = this.config.emailAddress;
						const password = this.config.password;

						this.config.imapUser = this.config.emailAddress;
						this.config.imapPassword = this.config.password;
						this.config.smtpUser = this.config.emailAddress;
						this.config.smtpPassword = this.config.password;
						this.firstToggle = false;
					}

					$(this.$refs.mailPassword).slideToggle(() => {
						$(this.$refs.mailAddress)
							.parent()
							.removeClass('groupmiddle')
							.addClass('groupbottom');
						// Focus imap host input
						this.$refs.imapHost.focus();
					});
				} else {
					$(this.$refs.mailPassword).slideToggle();
					$(this.$refs.mailAddress)
						.parent()
						.removeClass('groupbottom')
						.addClass('groupmiddle');
				}
			},
			onImapSslModeChange: function () {
				const imapDefaultPort = 143;
				const imapDefaultSecurePort = 993;

				switch (this.config.imapSslMode) {
					case 'none':
					case 'tls':
						this.config.imapPort = imapDefaultPort;
						break;
					case 'ssl':
						this.config.imapPort = imapDefaultSecurePort;
						break;
				}
			},
			onSmtpSslModeChange: function () {
				const smtpDefaultPort = 587;
				const smtpDefaultSecurePort = 465;

				switch (this.config.smtpSslMode) {
					case 'none':
					case 'tls':
						this.config.smtpPort = smtpDefaultPort;
						break;
					case 'ssl':
						this.config.smtpPort = smtpDefaultSecurePort;
						break;
				}
			},
			onSubmit: function () {
				const emailAddress = this.config.emailAddress
				const accountName = this.config.accountName;
				const password = this.config.password;

				let config = {
					accountName,
					emailAddress,
					password,
					autoDetect: true
				};

				// if manual setup is open, use manual values
				if (!this.config.autoDetect) {
					config = {
						accountName,
						emailAddress,
						password,
						imapHost: this.config.imapHost,
						imapPort: this.config.imapPort,
						imapSslMode: this.config.imapSslMode,
						imapUser: this.config.imapUser,
						imapPassword: this.config.imapPassword,
						smtpHost: this.config.smtpHost,
						smtpPort: this.config.smtpPort,
						smtpSslMode: this.config.smtpSslMode,
						smtpUser: this.config.smtpUser,
						smtpPassword: this.config.smtpPassword,
						autoDetect: false
					};
				}
				// TODO: Handle form submit
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

	.flex-row {
		display: flex;
	}
	label.button {
		display: inline-block;
		flex-grow: 1;
	}
	label input[type="radio"] {
		display: none;
	}
</style>