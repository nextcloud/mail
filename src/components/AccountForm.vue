<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form id="account-form" @submit.prevent="onSubmit">
		<AccountFormDiscoveryProgress
			v-if="discoveryProgress"
			:progress="discoveryProgress"
			:email="emailAddress"
			:message="loadingMessage"
			:choices="discoveredProtocols"
			@choose="onDiscoveryChoice" />
		<div v-show="!discoveryProgress" class="account-form__fields">
			<NcRadioGroup
				v-if="account?.protocol !== 'jmap'"
				v-model="mode"
				:label="t('mail', 'Mail server configuration mode')"
				hideLabel>
				<NcRadioGroupButton :label="t('mail', 'Auto')" value="auto" :disabled="loading" />
				<NcRadioGroupButton :label="t('mail', 'Manual')" value="manual" :disabled="loading" />
			</NcRadioGroup>
			<div v-show="mode === 'auto'" class="account-form__panel">
				<NcInputField
					id="auto-name"
					v-model="accountName"
					:label="t('mail', 'Name')"
					type="text"
					:placeholder="t('mail', 'Name')"
					:disabled="loading"
					autofocus />
				<NcInputField
					id="auto-address"
					v-model="emailAddress"
					:label="t('mail', 'Mail address')"
					:disabled="loading"
					:placeholder="t('mail', 'name@example.org')"
					required
					type="email"
					@change="clearFeedback" />
				<p v-if="!isValidEmail(emailAddress)" class="account-form--error">
					{{ t('mail', 'Please enter an email of the format name@example.com') }}
				</p>
				<NcPasswordField
					v-if="!isOidcAccount"
					id="auto-password"
					v-model="autoConfig.password"
					:disabled="loading"
					:label="optionalPasswordPlaceholder(t('mail', 'Password'))"
					:required="!hasPasswordAlternatives"
					@change="clearFeedback" />
				<NcCheckboxRadioSwitch
					id="auto-classification-enabled"
					v-model="classificationEnabled"
					:disabled="loading">
					{{ t('mail', 'Enable mark as important classification') }}
				</NcCheckboxRadioSwitch>
			</div>
			<div v-show="mode === 'manual'" class="account-form__panel">
				<NcInputField
					id="man-name"
					v-model="accountName"
					:label="t('mail', 'Name')"
					type="text"
					:placeholder="t('mail', 'Name')"
					:disabled="loading" />
				<NcInputField
					id="man-address"
					v-model="emailAddress"
					:label="t('mail', 'Mail address')"
					type="email"
					:placeholder="t('mail', 'name@example.org')"
					:disabled="loading"
					required
					@change="clearFeedback" />
				<p v-if="!isValidEmail(emailAddress)" class="account-form--error">
					{{ t('mail', 'Please enter an email of the format name@example.com') }}
				</p>

				<NcRadioGroup
					v-if="isSetup"
					v-model="protocol"
					:label="t('mail', 'Mail server protocol')"
					hideLabel>
					<NcRadioGroupButton :label="t('mail', 'IMAP/SMTP')" value="imap" :disabled="loading" />
					<NcRadioGroupButton :label="t('mail', 'JMAP')" value="jmap" :disabled="loading" />
				</NcRadioGroup>

				<AccountFormJmap
					v-if="isJmap"
					v-model="jmapConfig"
					:disabled="loading"
					:passwordOptional="!isSetup"
					@change="clearFeedback" />
				<AccountFormImap
					v-else
					v-model="imapConfig"
					:disabled="loading"
					:passwordOptional="!isSetup"
					:oauth="useOauth"
					@change="clearFeedback" />
				<NcCheckboxRadioSwitch
					v-if="isSetup"
					id="auto-classification-enabled"
					v-model="classificationEnabled"
					:disabled="loading">
					{{ t('mail', 'Enable mark as important classification') }}
				</NcCheckboxRadioSwitch>
			</div>
			<div v-if="isGoogleAccount && !googleOauthUrl" class="account-form__google-sso">
				{{ t('mail', 'Google requires OAuth authentication. If your Nextcloud admin has not configured Google OAuth, you can use a Google App Password instead.') }}
			</div>
			<div v-if="isMicrosoftAccount && !microsoftOauthUrl" class="account-form__microsoft-sso">
				{{ t('mail', 'Microsoft requires OAuth authentication. Ask your Nextcloud admin to configure Microsoft OAuth in the admin settings.') }}
			</div>
			<div v-if="isOidcAccount" class="account-form__oidc-sso">
				{{ t('mail', 'This email domain uses single sign-on. You will be redirected to your identity provider to grant access to your mail account.') }}
			</div>
			<div class="account-form__submit-buttons">
				<NcButton
					v-if="mode === 'auto'"
					:aria-label="submitButtonText"
					class="account-form__submit-button"
					variant="primary"
					type="submit"
					:disabled="isDisabledAuto || loading"
					@click.prevent="onSubmit">
					<template #icon>
						<NcLoadingIcon v-if="loading" :size="20" />
						<IconCheck v-else :size="20" />
					</template>
					{{ submitButtonText }}
				</NcButton>
				<NcButton
					v-else-if="mode === 'manual'"
					:aria-label="submitButtonText"
					class="account-form__submit-button"
					variant="primary"
					type="submit"
					:disabled="isDisabledManual || loading"
					@click.prevent="onSubmit">
					<template #icon>
						<NcLoadingIcon v-if="loading" :size="20" />
						<IconCheck v-else :size="20" />
					</template>
					{{ submitButtonText }}
				</NcButton>
			</div>
		</div>
		<div v-if="feedback" class="account-form--feedback">
			{{ feedback }}
		</div>
	</form>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { mapState, mapStores } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import IconCheck from 'vue-material-design-icons/Check.vue'
import AccountFormDiscoveryProgress from './AccountFormDiscoveryProgress.vue'
import AccountFormImap from './AccountFormImap.vue'
import AccountFormJmap from './AccountFormJmap.vue'
import { CONSENT_ABORTED, getUserConsent } from '../integration/oauth.js'
import logger from '../logger.js'
import { PROTOCOL_IMAP, PROTOCOL_JMAP } from '../models/accountServerConfig.ts'
import {
	queryIspdb,
	queryMx,
	testConnectivity,
} from '../service/AutoConfigService.js'
import { discover, DiscoveryError } from '../service/DiscoveryService.ts'
import { generateOauthState } from '../service/OauthStateService.js'
import useMainStore from '../store/mainStore.js'
import {
	candidateFromEvent,
	initialDiscoveryProgress,
	isUsableCandidate,
	orderCandidates,
	toImapServerConfig,
	toJmapServerConfig,
	updateDiscoveryProgress,
} from '../util/discovery.ts'
import { isValidEmail } from '../util/emailAddress.js'
import { findProviderForEmail, loadOidcProviders, openOidcConsent } from '../util/oidc.js'

export default {
	name: 'AccountForm',
	components: {
		AccountFormDiscoveryProgress,
		AccountFormImap,
		AccountFormJmap,
		NcPasswordField,
		NcInputField,
		NcCheckboxRadioSwitch,
		NcRadioGroup,
		NcRadioGroupButton,
		NcButton,
		NcLoadingIcon,
		IconCheck,
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

		account: {
			type: Object,
			required: false,
			default: () => undefined,
		},
	},

	emits: ['accountCreated'],

	data() {
		const fromAccountOr = (prop, def) => {
			if (this.account !== undefined) {
				return this.account[prop]
			} else {
				return def
			}
		}

		const editsJmap = this.account?.protocol === PROTOCOL_JMAP

		return {
			loading: false,
			loadingMessage: undefined,
			mode: this.account !== undefined ? 'manual' : 'auto',
			protocol: editsJmap ? PROTOCOL_JMAP : PROTOCOL_IMAP,
			accountName: this.displayName,
			emailAddress: this.email,
			classificationEnabled: fromAccountOr('classificationEnabled', loadState('mail', 'importance_classification_default', true)),
			autoConfig: {
				password: '',
			},

			imapConfig: {
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
				syncSmtpCredentials: true,
			},

			jmapConfig: {
				host: editsJmap ? this.account.imapHost : '',
				port: editsJmap ? this.account.imapPort : 443,
				sslMode: editsJmap ? this.account.imapSslMode : 'yes',
				user: editsJmap ? this.account.imapUser : '',
				password: '',
				path: (editsJmap ? this.account.path : null) ?? '',
			},

			feedback: null,
			discoveryProgress: null,
			discoveryController: null,
			discoveryChoices: [],
			resolveDiscoveryChoice: null,
			password: '',
			oidcProviders: loadOidcProviders(),
		}
	},

	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, [
			'googleOauthUrl',
			'microsoftOauthUrl',
		]),

		isSetup() {
			return this.account === undefined
		},

		isDisabledAuto() {
			if (this.loading) {
				return true
			}

			if (this.mode !== 'auto') {
				return this.loading
			}

			let requiresPassword = false
			if (this.isSetup) {
				requiresPassword = !this.autoConfig.password && !this.googleOauthUrl && !this.microsoftOauthUrl
			}

			if (!this.emailAddress || !isValidEmail(this.emailAddress) || requiresPassword) {
				return true
			}

			if (this.isOidcAccount) {
				return false
			}

			return (!this.googleOauthUrl && !this.autoConfig.password)
				|| (!this.microsoftOauthUrl && !this.autoConfig.password)
		},

		isDisabledManual() {
			if (this.loading) {
				return true
			}

			if (this.mode !== 'manual') {
				return this.loading
			}

			if (this.isJmap) {
				return !this.emailAddress || !isValidEmail(this.emailAddress)
					|| !this.jmapConfig.host || !this.jmapConfig.port || !this.jmapConfig.user
					|| (this.isSetup && !this.jmapConfig.password)
			}

			let requiresPassword = false
			if (this.isSetup) {
				requiresPassword = !this.useOauth && (!this.imapConfig.imapPassword || !this.imapConfig.smtpPassword)
			}

			return !this.emailAddress || !isValidEmail(this.emailAddress)
				|| !this.imapConfig.imapHost || !this.imapConfig.imapPort || !this.imapConfig.imapUser
				|| !this.imapConfig.smtpHost || !this.imapConfig.smtpPort || !this.imapConfig.smtpUser
				|| requiresPassword
		},

		discoveredProtocols() {
			return this.discoveryChoices.map((candidate) => candidate.protocol)
		},

		isJmap() {
			return this.protocol === PROTOCOL_JMAP
		},

		isGoogleAccount() {
			return !this.isJmap && (this.imapConfig.imapHost === 'imap.gmail.com'
				|| this.imapConfig.smtpHost === 'smtp.gmail.com')
		},

		isMicrosoftAccount() {
			return !this.isJmap && (this.imapConfig.imapHost === 'outlook.office365.com'
				|| this.imapConfig.smtpHost === 'outlook.office365.com')
		},

		/**
		 * The admin-configured OIDC provider matching the entered email's domain, if any.
		 *
		 * @return {?object}
		 */
		oidcProvider() {
			return findProviderForEmail(this.emailAddress, this.oidcProviders)
		},

		isOidcAccount() {
			return !this.isJmap && !this.isGoogleAccount && !this.isMicrosoftAccount && !!this.oidcProvider
		},

		hasPasswordAlternatives() {
			return !!this.googleOauthUrl
				|| !!this.microsoftOauthUrl
		},

		useOauth() {
			return (this.isGoogleAccount && this.googleOauthUrl)
				|| (this.isMicrosoftAccount && this.microsoftOauthUrl)
				|| this.isOidcAccount
		},

		submitButtonText() {
			if (this.loading) {
				return this.loadingMessage ?? t('mail', 'Connecting')
			}
			if (this.isOidcAccount) {
				const provider = this.oidcProvider.name || this.oidcProvider.emailDomain
				return this.account
					? t('mail', 'Reconnect to {provider}', { provider })
					: t('mail', 'Connect to {provider}', { provider })
			}
			if (this.mode === 'manual' && this.useOauth) {
				if (this.isGoogleAccount) {
					return this.account ? t('mail', 'Reconnect Google account') : t('mail', 'Sign in with Google')
				} else {
					return this.account ? t('mail', 'Reconnect Microsoft account') : t('mail', 'Sign in with Microsoft')
				}
			}
			return this.account ? t('mail', 'Save') : t('mail', 'Connect')
		},
	},

	watch: {
		oidcProvider: {
			immediate: true,
			handler(provider) {
				if (provider && this.isSetup) {
					this.applyOidcProvider(provider)
				}
			},
		},

		mode: {
			immediate: true,
			handler(mode) {
				if (mode !== 'manual') {
					if (this.isSetup) {
						this.protocol = PROTOCOL_IMAP
					}
					return
				}

				// IMAP
				if (this.imapConfig.imapUser === '') {
					this.imapConfig.imapUser = this.emailAddress
				}
				if (this.imapConfig.imapPassword === '') {
					this.imapConfig.imapPassword = this.autoConfig.password
				}

				// SMTP
				if (!this.imapConfig.smtpUser) {
					this.imapConfig.smtpUser = this.emailAddress
				}
				if (this.imapConfig.smtpPassword === '') {
					this.imapConfig.smtpPassword = this.autoConfig.password
				}

				// JMAP
				if (this.jmapConfig.user === '') {
					this.jmapConfig.user = this.emailAddress
				}
				if (this.jmapConfig.password === '') {
					this.jmapConfig.password = this.autoConfig.password
				}
			},
		},

		emailAddress(newEmail, oldEmail) {
			if (this.useOauth && this.imapConfig.smtpUser === oldEmail) {
				this.imapConfig.smtpUser = newEmail
			}
		},
	},

	beforeUnmount() {
		this.discoveryController?.abort()
	},

	methods: {
		isValidEmail,

		applyOidcProvider(provider) {
			this.imapConfig.imapHost = provider.imapHost
			this.imapConfig.imapPort = provider.imapPort
			this.imapConfig.imapSslMode = provider.imapSslMode
			this.imapConfig.imapUser = this.emailAddress
			this.imapConfig.smtpHost = provider.smtpHost
			this.imapConfig.smtpPort = provider.smtpPort
			this.imapConfig.smtpSslMode = provider.smtpSslMode
			this.imapConfig.smtpUser = this.emailAddress
		},

		async requestOauthConsent(account, isUpdate) {
			this.loadingMessage = t('mail', 'Awaiting user consent')
			if (this.isGoogleAccount) {
				this.feedback = isUpdate
					? t('mail', 'Account updated. Please follow the pop-up instructions to reconnect your Google account')
					: t('mail', 'Account created. Please follow the pop-up instructions to link your Google account')
				await getUserConsent(this.googleOauthUrl
					.replace('_state_', await generateOauthState(account.id))
					.replace('_email_', encodeURIComponent(account.emailAddress)))
			} else if (this.isMicrosoftAccount) {
				this.feedback = isUpdate
					? t('mail', 'Account updated. Please follow the pop-up instructions to reconnect your Microsoft account')
					: t('mail', 'Account created. Please follow the pop-up instructions to link your Microsoft account')
				await getUserConsent(this.microsoftOauthUrl
					.replace('_state_', await generateOauthState(account.id))
					.replace('_email_', encodeURIComponent(account.emailAddress)))
			} else {
				const provider = this.oidcProvider.name || this.oidcProvider.emailDomain
				this.feedback = isUpdate
					? t('mail', 'Account updated. Please follow the pop-up instructions to reconnect your {provider} account', { provider })
					: t('mail', 'Account created. Please follow the pop-up instructions to link your {provider} account', { provider })
				await openOidcConsent(this.oidcProvider, account.id)
			}
		},

		clearFeedback() {
			this.feedback = null
		},

		optionalPasswordPlaceholder(fallback) {
			if (this.isSetup) {
				return fallback
			} else {
				return t('mail', 'Leave blank to not change password')
			}
		},

		applyAutoConfig(config) {
			if (!config) {
				return false
			}
			if (config?.imapConfig) {
				this.imapConfig.imapUser = config.imapConfig.username
				this.imapConfig.imapHost = config.imapConfig.host
				this.imapConfig.imapPort = config.imapConfig.port
				this.imapConfig.imapSslMode = config.imapConfig.security
				this.imapConfig.imapPassword = this.autoConfig.password
			}
			if (config?.smtpConfig) {
				this.imapConfig.smtpUser = config.smtpConfig.username
				this.imapConfig.smtpHost = config.smtpConfig.host
				this.imapConfig.smtpPort = config.smtpConfig.port
				this.imapConfig.smtpSslMode = config.smtpConfig.security
				this.imapConfig.smtpPassword = this.autoConfig.password
			}
			return true
		},

		async detectConfig() {
			this.loadingMessage = t('mail', 'Looking up configuration')
			const config = await queryIspdb(this.emailAddress.split('@').pop(), this.emailAddress)
			logger.debug('fetched auto config', { config })
			// Apply settings to manual mode before submitting so the user
			// can make modifications if the config fails
			if (this.applyAutoConfig(config)) {
				logger.debug('ISP DB config applied')
				return true
			} else {
				const mxHosts = await queryMx(this.emailAddress)
				logger.debug('MX hosts fetched', { mxHosts })

				if (mxHosts.length) {
					// Try the TLD of the MX
					// FIXME: breaks with eTLDs like .co.uk
					const tldMx = mxHosts[0].split('.').splice(-2).join('.').toLowerCase()
					const mxConfig = await queryIspdb(
						tldMx,
						this.emailAddress,
					)
					logger.debug('fetched MX auto config', { mxConfig })
					if (mxConfig && this.applyAutoConfig(mxConfig)) {
						return true
					}
				}

				// Test the highest priority MX for open IMAP/SMTP ports
				this.loadingMessage = t('mail', 'Checking mail host connectivity')
				const imapAndSmtpHosts = mxHosts.slice(0, 1).flatMap((host) => {
					return [993, 143, 465, 587].map((port) => ({
						host,
						port,
					}))
				})
				const results = await Promise.all(imapAndSmtpHosts.map(async ({ host, port }) => {
					return {
						host,
						port,
						canConnect: await testConnectivity(host, port),
					}
				}))
				const firstImapHost = results.filter(({ canConnect, port }) => canConnect && port === 993)[0]
				const firstSmtpHost = results.filter(({ canConnect, port }) => canConnect && [465, 587].includes(port))[0]
				logger.debug('MX connectivity tested', { firstImapHost, firstSmtpHost })
				if (firstImapHost && firstSmtpHost) {
					this.applyAutoConfig({
						imapConfig: {
							username: this.emailAddress, // Assumption
							host: firstImapHost.host,
							port: firstImapHost.port,
							security: firstImapHost.port === 993 ? 'ssl' : 'tls',
						},
						smtpConfig: {
							username: this.emailAddress, // Assumption
							host: firstSmtpHost.host,
							port: firstSmtpHost.port,
							security: firstSmtpHost.port === 465 ? 'ssl' : 'tls',
						},
					})
					return true
				} else {
					this.feedback = t('mail', 'Configuration discovery failed. Please use the manual settings')
				}
				return false
			}
		},

		async onSubmit(event) {
			logger.debug('account form submitted', { event })
			if (this.isDisabledManual || this.isDisabledAuto) {
				logger.warn('submit is disabled')
				return
			}
			this.clearFeedback()
			this.loading = true
			try {
				if (this.mode === 'auto' && this.isOidcAccount) {
					this.applyOidcProvider(this.oidcProvider)
					await this.saveAccount()
				} else if (this.mode === 'auto') {
					await this.discoverAndSave()
				} else {
					await this.saveAccount()
				}
			} catch (error) {
				this.showError(error)
			} finally {
				this.loading = false
				this.loadingMessage = undefined
				this.discoveryProgress = null
			}
		},

		async discoverCandidates() {
			this.loadingMessage = t('mail', 'Looking up configuration')
			this.discoveryProgress = initialDiscoveryProgress()
			this.discoveryController = new AbortController()
			const candidates = []
			try {
				await discover(this.emailAddress, (event) => {
					this.discoveryProgress = updateDiscoveryProgress(this.discoveryProgress, event)
					if (event.type === 'candidate') {
						candidates.push(candidateFromEvent(event))
					}
				}, this.discoveryController.signal)
			} finally {
				this.discoveryController = null
			}
			logger.debug('discovered candidates', { candidates })

			if (this.isSetup) {
				return orderCandidates(candidates)
			}
			const ownProtocol = []
			for (const candidate of candidates) {
				if (candidate.protocol === this.account.protocol) {
					ownProtocol.push(candidate)
				}
			}
			return ownProtocol
		},

		applyCandidate(candidate) {
			if (candidate.protocol === PROTOCOL_JMAP) {
				this.protocol = PROTOCOL_JMAP
				this.jmapConfig = toJmapServerConfig(candidate.jmap, this.autoConfig.password)
			} else {
				this.protocol = PROTOCOL_IMAP
				this.imapConfig = toImapServerConfig(candidate.imap, this.autoConfig.password)
			}
		},

		async discoverAndSave() {
			const candidates = await this.discoverCandidates()
			const usable = []
			for (const candidate of candidates) {
				if (isUsableCandidate(candidate)) {
					usable.push(candidate)
				}
			}

			if (usable.length === 0) {
				if (candidates.length > 0) {
					this.applyCandidate(candidates[0])
					this.mode = 'manual'
				}
				this.feedback = t('mail', 'Configuration discovery failed. Please use the manual settings')
				return
			}

			const candidate = usable.length === 1 ? usable[0] : await this.chooseCandidate(usable)
			this.applyCandidate(candidate)
			try {
				await this.saveAccount()
			} catch (error) {
				if (error.data?.service !== undefined) {
					this.mode = 'manual'
				}
				throw error
			}
		},

		chooseCandidate(candidates) {
			this.loadingMessage = t('mail', 'Your mail server supports more than one protocol. Choose how to connect.')
			this.discoveryChoices = candidates
			return new Promise((resolve) => {
				this.resolveDiscoveryChoice = (protocol) => {
					this.discoveryChoices = []
					this.resolveDiscoveryChoice = null
					resolve(candidates.find((candidate) => candidate.protocol === protocol))
				}
			})
		},

		onDiscoveryChoice(protocol) {
			this.resolveDiscoveryChoice?.(protocol)
		},

		async saveAccount() {
			if (!this.useOauth && this.isSetup) {
				if (this.mode === 'auto' && this.autoConfig.password === '') {
					this.feedback = t('mail', 'Password required')
					return
				}
				if (this.isJmap && this.jmapConfig.password === '') {
					this.feedback = t('mail', 'Password required')
					return
				}
				if (this.mode === 'manual' && !this.isJmap && (this.imapConfig.imapPassword === '' || this.imapConfig.smtpPassword === '')) {
					this.feedback = t('mail', 'Password required')
					return
				}
			}
			this.loadingMessage = t('mail', 'Testing authentication')
			let data
			if (this.isJmap) {
				data = {
					protocol: PROTOCOL_JMAP,
					accountName: this.accountName,
					emailAddress: this.emailAddress,
					imapHost: this.jmapConfig.host.trim(),
					imapPort: this.jmapConfig.port,
					imapSslMode: this.jmapConfig.sslMode,
					imapUser: this.jmapConfig.user,
					path: this.jmapConfig.path.trim() || null,
					authMethod: 'password',
					classificationEnabled: this.classificationEnabled,
				}
				if (this.jmapConfig.password !== '') {
					data.imapPassword = this.jmapConfig.password
				}
			} else {
				data = {
					...this.imapConfig,
					accountName: this.accountName,
					emailAddress: this.emailAddress,
					imapHost: this.imapConfig.imapHost.trim(),
					smtpHost: this.imapConfig.smtpHost.trim(),
					authMethod: this.useOauth ? 'xoauth2' : 'password',
					classificationEnabled: this.classificationEnabled,
				}
				if (this.useOauth) {
					delete data.imapPassword
					delete data.smtpPassword
				} else if (!this.isSetup) {
					if (data.imapPassword === '') {
						delete data.imapPassword
					}
					if (data.smtpPassword === '') {
						delete data.smtpPassword
					}
				}
			}
			if (!this.account) {
				const account = await this.mainStore.startAccountSetup(data)
				if (this.useOauth) {
					try {
						await this.requestOauthConsent(account, false)
					} catch (e) {
						// Clean up the temporary account before we continue
						this.mainStore.deleteAccount(account)
						logger.info(`Temporary account ${account.id} deleted`)
						throw e
					}
					this.clearFeedback()
				}
				this.loadingMessage = t('mail', 'Loading account')
				await this.mainStore.finishAccountSetup({ account })
				this.$emit('accountCreated', account)
			} else {
				const oldAccountData = this.account
				const account = await this.mainStore.updateAccount({
					...data,
					accountId: this.account.id,
				})
				if (this.useOauth) {
					try {
						await this.requestOauthConsent(account, true)
					} catch (e) {
						// Undo changes
						await this.mainStore.updateAccount({
							...oldAccountData,
							accountId: oldAccountData.id,
						})
						logger.info(`Account ${account.id} update undone`)
						throw e
					}
					this.clearFeedback()
				}
				this.feedback = t('mail', 'Account updated')
			}
		},

		showError(error) {
			logger.error('could not save account details', { error })

			if (error.data?.error === 'CONNECTION_ERROR') {
				if (error.data.service === 'IMAP') {
					this.feedback = t('mail', 'IMAP server is not reachable')
				} else if (error.data.service === 'SMTP') {
					this.feedback = t('mail', 'SMTP server is not reachable')
				} else if (error.data.service === 'JMAP') {
					this.feedback = t('mail', 'JMAP server is not reachable')
				}
			} else if (error.data?.error === 'ACCOUNT_EXISTS') {
				this.feedback = t('mail', 'Account already exists')
			} else if (error.data?.error === 'AUTHENTICATION_WRONG_PASSWORD') {
				if (error.data.service === 'IMAP') {
					this.feedback = t('mail', 'IMAP username or password is wrong')
				} else if (error.data.service === 'SMTP') {
					this.feedback = t('mail', 'SMTP username or password is wrong')
				} else if (error.data.service === 'JMAP') {
					this.feedback = t('mail', 'JMAP username or password is wrong')
				}
			} else if (error.data?.error === 'AUTHENTICATION_DENIED') {
				if (error.data.service === 'IMAP') {
					this.feedback = t('mail', 'IMAP server denied authentication')
				} else if (error.data.service === 'SMTP') {
					this.feedback = t('mail', 'SMTP server denied authentication')
				} else if (error.data.service === 'JMAP') {
					this.feedback = t('mail', 'JMAP server denied authentication')
				}
			} else if (error.data?.error === 'AUTHENTICATION') {
				if (error.data.service === 'IMAP') {
					this.feedback = t('mail', 'IMAP authentication error')
				} else if (error.data.service === 'SMTP') {
					this.feedback = t('mail', 'SMTP authentication error')
				}
			} else {
				if (error.data?.service === 'IMAP') {
					this.feedback = t('mail', 'IMAP connection failed')
				} else if (error.data?.service === 'SMTP') {
					this.feedback = t('mail', 'SMTP connection failed')
				} else if (error.data?.service === 'JMAP') {
					this.feedback = t('mail', 'JMAP connection failed')
				} else if (error.message === CONSENT_ABORTED) {
					this.feedback = t('mail', 'Authorization pop-up closed')
				} else if (error.response?.status === 429 || (error instanceof DiscoveryError && error.status === 429)) {
					this.feedback = t('mail', 'Configuration discovery temporarily not available. Please try again later.')
				} else {
					this.feedback = t('mail', 'There was an error while setting up your account')
				}
			}
		},
	},
}
</script>

<style lang="scss" scoped>
:deep(.input-field) {
	margin: calc(var(--default-grid-baseline) * 3) 0;
}

.account-form {
	&__submit-buttons {
		display: flex;
		justify-content: center;
		margin-top: var(--default-grid-baseline);
	}

	&__submit-button {
		display: flex;
		align-items: center;
	}

	&--feedback {
		color: var(--color-text-maxcontrast);
		margin-top: var(--default-grid-baseline);
		text-align: center;
	}

	&--error {
		text-align: start;
		font-size: 14px;
	}
}

#account-form {
	z-index: 1001;
	width: 300px;
	top: 15%;
	padding-bottom: calc(var(--default-grid-baseline) * 12);
	margin: 0 auto;
	padding-top: calc(var(--default-grid-baseline) * 7);
}
</style>
