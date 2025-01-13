<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="app-settings">
		<NcAppSettingsDialog id="app-settings-dialog"
			:name="t('mail', 'Mail settings')"
			:show-navigation="true"
			:open.sync="showSettings">
			<NcAppSettingsSection id="account-creation" :name="t('mail', 'Accounts')">
				<NcButton v-if="allowNewMailAccounts"
					type="primary"
					to="/setup"
					:aria-label="t('mail', 'Add mail account')"
					class="app-settings-button">
					<template #icon>
						<IconAdd :size="16" />
					</template>
					{{ t('mail', 'Add mail account') }}
				</NcButton>

				<h6>{{ t('mail', 'Account settings') }}</h6>
				<p>{{ t('mail', 'Settings for:') }}</p>
				<li v-for="account in getAccounts" :key="account.id">
					<NcButton v-if="account && account.emailAddress"
						class="app-settings-button"
						type="secondary"
						:aria-label="t('mail', 'Account settings')"
						@click="openAccountSettings(account.id)">
						{{ account.emailAddress }}
					</NcButton>
				</li>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="appearance-and-accessibility" :name="t('mail', 'General')">
				<h6>{{ t('mail', 'Layout') }}</h6>
				<NcCheckboxRadioSwitch value="no-split"
					:button-variant="true"
					name="mail-layout"
					type="radio"
					:checked="layoutMode"
					button-variant-grouped="vertical"
					@update:checked="setLayout('no-split')">
					<template #icon>
						<CompactMode :size="16" />
					</template>
					{{ t('mail', 'List') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch value="vertical-split"
					:button-variant="true"
					name="mail-layout"
					type="radio"
					:checked="layoutMode"
					button-variant-grouped="vertical"
					@update:checked="setLayout('vertical-split')">
					<template #icon>
						<VerticalSplit :size="16" />
					</template>
					{{ t('mail', 'Vertical split') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch value="horizontal-split"
					:button-variant="true"
					name="mail-layout"
					type="radio"
					:checked="layoutMode"
					button-variant-grouped="vertical"
					@update:checked="setLayout('horizontal-split')">
					<template #icon>
						<HorizontalSplit :size="16" />
					</template>
					{{ t('mail', 'Horizontal split') }}
				</NcCheckboxRadioSwitch>

				<h6>{{ t('mail', 'Sorting') }}</h6>
				<div class="sorting">
					<NcCheckboxRadioSwitch class="sorting__switch"
						:checked="sortOrder"
						value="newest"
						name="order_radio"
						type="radio"
						@update:checked="onSortByDate">
						{{ t('mail', 'Newest') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch class="sorting__switch"
						:checked="sortOrder"
						value="oldest"
						name="order_radio"
						type="radio"
						@update:checked="onSortByDate">
						{{ t('mail', 'Oldest') }}
					</NcCheckboxRadioSwitch>
				</div>

				<h6>{{ t('mail', 'Search in body') }}</h6>
				<p v-if="loadingPrioritySettings" class="app-settings">
					{{ prioritySettingsText }}
				</p>
				<p v-else class="app-settings">
					<input id="priority-inbox-toggle"
						class="checkbox"
						type="checkbox"
						:checked="searchPriorityBody"
						@change="onToggleSearchPriorityBody">
					<label for="priority-inbox-toggle">{{ prioritySettingsText }}</label>
				</p>

				<h6>{{ t('mail', 'Reply text position') }}</h6>
				<p v-if="loadingReplySettings" class="app-settings reply-settings">
					<IconLoading :size="16" />
					{{ replySettingsText }}
				</p>
				<p v-else class="app-settings">
					<input id="bottom-reply-enabled"
						class="checkbox"
						type="checkbox"
						:checked="useBottomReplies"
						@change="onToggleButtonReplies">
					<label for="bottom-reply-enabled">{{ replySettingsText }}</label>
				</p>

				<h6>{{ t('mail', 'Gravatar settings') }}</h6>
				<p v-if="loadingAvatarSettings" class="app-settings avatar-settings">
					<IconLoading :size="16" />
					{{ t('mail', 'Use Gravatar and favicon avatars') }}
				</p>
				<p v-else class="app-settings">
					<input id="gravatar-enabled"
						class="checkbox"
						type="checkbox"
						:checked="useExternalAvatars"
						@change="onToggleExternalAvatars">
					<label for="gravatar-enabled">{{ t('mail', 'Use Gravatar and favicon avatars') }}</label>
				</p>

				<h6>{{ t('mail', 'Mailto') }}</h6>
				<p class="settings-hint">
					{{ t('mail', 'Register as application for mail links') }}
				</p>
				<NcButton type="secondary"
					class="app-settings-button"
					:aria-label="t('mail', 'Register as application for mail links')"
					@click="registerProtocolHandler">
					<template #icon>
						<IconEmail :size="16" />
					</template>
					{{ t('mail', 'Register') }}
				</NcButton>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="privacy-and-security" :name="t('mail', 'Privacy and security')">
				<h6>{{ t('mail', 'Data collection consent') }}</h6>
				<p class="settings-hint">
					{{ t('mail', 'Allow the app to collect data about your interactions. Based on this data, the app will adapt to your preferences. The data will only be stored locally.') }}
				</p>
				<p v-if="loadingOptOutSettings" class="app-settings">
					<IconLoading :size="16" />
					{{ optOutSettingsText }}
				</p>
				<p v-else class="app-settings">
					<input id="data-collection-toggle"
						class="checkbox"
						type="checkbox"
						:checked="useDataCollection"
						@change="onToggleCollectData">
					<label for="data-collection-toggle">{{ optOutSettingsText }}</label>
				</p>

				<h6>{{ t('mail', 'Trusted senders') }}</h6>
				<TrustedSenders />

				<h6>{{ t('mail', 'Internal addresses') }}</h6>
				<p class="settings-hint">
					{{ t('mail', 'Highlight external email addresses by enabling this feature, manage your internal addresses and domains to ensure recognized contacts stay unmarked.') }}
				</p>
				<p class="app-settings">
					<input id="internal-address-toggle"
						class="checkbox"
						type="checkbox"
						:checked="useInternalAddresses"
						@change="onToggleInternalAddress">
					<label for="internal-address-toggle">{{ internalAddressText }}</label>
				</p>
				<InternalAddress />

				<h6>{{ t('mail', 'S/MIME') }}</h6>
				<NcButton class="app-settings-button"
					type="secondary"
					:aria-label="t('mail', 'Manage S/MIME certificates')"
					@click.prevent.stop="displaySmimeCertificateModal = true">
					<template #icon>
						<IconLock :size="16" />
					</template>
					{{ t('mail', 'Manage S/MIME certificates') }}
				</NcButton>
				<SmimeCertificateModal v-if="displaySmimeCertificateModal"
					@close="displaySmimeCertificateModal = false" />

				<h6>{{ t('mail', 'Mailvelope') }}</h6>
				<div class="mailvelope-section">
					<div v-if="mailvelopeIsAvailable">
						{{ t('mail', 'Mailvelope is enabled for the current domain!') }}
					</div>
					<div v-else>
						<p>
							{{ t('mail', 'Looking for a way to encrypt your emails?') }}
						</p>
						<p>
							<a href="https://www.mailvelope.com/"
								target="_blank"
								class="button"
								rel="noopener noreferrer">
								{{ t('mail', 'Install Mailvelope browser extension by clicking here') }}
							</a>
						</p>
						<p>
							<a class="button"
								@click="mailvelopeAuthorizeDomain">
								{{ t('mail', 'Enable Mailvelope for the current domain') }}
							</a>
						</p>
					</div>
				</div>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="autotagging-settings" :name="t('mail', 'Assistance features')">
				<p v-if="toggleAutoTagging" class="app-settings">
					<IconLoading :size="16" />
				</p>
				<p v-else class="app-settings">
					<input id="auto-tagging-toggle"
						class="checkbox"
						type="checkbox"
						:checked="useAutoTagging"
						@change="onToggleAutoTagging">
					<label for="auto-tagging-toggle">{{ autoTaggingText }}</label>
				</p>
				<p v-if="followUpFeatureAvailable" class="app-settings">
					<input id="follow-up-reminder-toggle"
						class="checkbox"
						type="checkbox"
						:checked="useFollowUpReminders"
						@change="onToggleFollowUpReminders">
					<label for="follow-up-reminder-toggle">{{ followUpReminderText }}</label>
				</p>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="keyboard-shortcut-settings" :name="t('mail', 'Keyboard shortcuts')">
				<dl>
					<div>
						<dt><kbd>C</kbd></dt>
						<dd>{{ t('mail', 'Compose new message') }}</dd>
					</div>
					<div>
						<dt><kbd>←</kbd></dt>
						<dd>{{ t('mail', 'Newer message') }}</dd>
					</div>
					<div>
						<dt><kbd>→</kbd></dt>
						<dd>{{ t('mail', 'Older message') }}</dd>
					</div>

					<div>
						<dt><kbd>S</kbd></dt>
						<dd>{{ t('mail', 'Toggle star') }}</dd>
					</div>
					<div>
						<dt><kbd>U</kbd></dt>
						<dd>{{ t('mail', 'Toggle unread') }}</dd>
					</div>
					<div>
						<dt><kbd>A</kbd></dt>
						<dd>{{ t('mail', 'Archive') }}</dd>
					</div>
					<div>
						<dt><kbd>Del</kbd></dt>
						<dd>{{ t('mail', 'Delete') }}</dd>
					</div>

					<div>
						<dt><kbd>Ctrl</kbd> + <kbd>F</kbd></dt>
						<dd>{{ t('mail', 'Search') }}</dd>
					</div>
					<div>
						<dt><kbd>Ctrl</kbd> + <kbd>Enter</kbd></dt>
						<dd>{{ t('mail', 'Send') }}</dd>
					</div>
					<div>
						<dt><kbd>R</kbd></dt>
						<dd>{{ t('mail', 'Refresh') }}</dd>
					</div>
				</dl>
			</NcAppSettingsSection>
		</NcAppSettingsDialog>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import CompactMode from 'vue-material-design-icons/ReorderHorizontal.vue'

import { NcAppSettingsSection, NcAppSettingsDialog, NcButton, NcLoadingIcon as IconLoading, NcCheckboxRadioSwitch } from '@nextcloud/vue'

import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconEmail from 'vue-material-design-icons/Email.vue'
import IconLock from 'vue-material-design-icons/Lock.vue'
import VerticalSplit from 'vue-material-design-icons/FormatColumns.vue'
import HorizontalSplit from 'vue-material-design-icons/ViewSplitHorizontal.vue'
import Logger from '../logger.js'
import SmimeCertificateModal from './smime/SmimeCertificateModal.vue'
import TrustedSenders from './TrustedSenders.vue'
import InternalAddress from './InternalAddress.vue'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile.js'
import useMainStore from '../store/mainStore.js'
import { mapStores, mapState } from 'pinia'

export default {
	name: 'AppSettingsMenu',
	components: {
		TrustedSenders,
		InternalAddress,
		NcButton,
		IconEmail,
		IconAdd,
		IconLoading,
		IconLock,
		SmimeCertificateModal,
		NcCheckboxRadioSwitch,
		NcAppSettingsDialog,
		NcAppSettingsSection,
		CompactMode,
		VerticalSplit,
		HorizontalSplit,
	},
	mixins: [isMobile],
	props: {
		open: {
			required: true,
			type: Boolean,
		},
	},
	data() {
		return {
			loadingAvatarSettings: false,
			prioritySettingsText: t('mail', 'Search in the body of messages in priority Inbox'),
			loadingPrioritySettings: false,
			// eslint-disable-next-line
			optOutSettingsText: t('mail', 'Activate'),
			loadingOptOutSettings: false,
			// eslint-disable-next-line
			replySettingsText: t('mail', 'Put my text to the bottom of a reply instead of on top of it.'),
			loadingReplySettings: false,
			// eslint-disable-next-line
			autoTaggingText: t('mail', 'Mark as important'),
			// eslint-disable-next-line
			followUpReminderText: t('mail', 'Remind about messages that require a reply but received none'),
			internalAddressText: t('mail', 'Use internal addresses'),
			toggleAutoTagging: false,
			displaySmimeCertificateModal: false,
			sortOrder: 'newest',
			showSettings: false,
			showAccountSettings: false,
			showMailSettings: true,
			selectedAccount: null,
			mailvelopeIsAvailable: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, ['getAccounts', 'followUpFeatureAvailable']),
		searchPriorityBody() {
			return this.mainStore.getPreference('search-priority-body', 'false') === 'true'
		},
		useBottomReplies() {
			return this.mainStore.getPreference('reply-mode', 'top') === 'bottom'
		},
		useExternalAvatars() {
			return this.mainStore.getPreference('external-avatars', 'true') === 'true'
		},
		useDataCollection() {
			return this.mainStore.getPreference('collect-data', 'true') === 'true'
		},
		useAutoTagging() {
			return this.mainStore.getPreference('tag-classified-messages', 'true') === 'true'
		},
		useInternalAddresses() {
			return this.mainStore.getPreference('internal-addresses', 'false') === 'true'
		},
		useFollowUpReminders() {
			return this.mainStore.getPreference('follow-up-reminders', 'true') === 'true'
		},
		allowNewMailAccounts() {
			return this.mainStore.getPreference('allow-new-accounts', true)
		},
		layoutMode() {
			return this.mainStore.getPreference('layout-mode', 'vertical-split')
		},
	},
	watch: {
		showSettings(value) {
			if (!value) {
				this.$emit('update:open', value)
			}
		},
		async open(value) {
			if (value) {
				await this.onOpen()
			}
		},
	},
	mounted() {
		this.sortOrder = this.mainStore.getPreference('sort-order', 'newest')
		document.addEventListener.call(window, 'mailvelope', () => this.checkMailvelope())
	},
	updated() {
		this.checkMailvelope()
	},
	methods: {
		closeAccountSettings() {
			this.showAccountSettings = false
		},
		openAccountSettings(accountId) {
			this.mainStore.showSettingsForAccountMutation(accountId)
			this.showSettings = false
		},
		checkMailvelope() {
			this.mailvelopeIsAvailable = !!window.mailvelope
		},
		async setLayout(layoutMode) {
			try {
				await this.mainStore.savePreference({
					key: 'layout-mode',
					value: layoutMode,
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
			}
		},
		async onOpen() {
			this.showSettings = true
		},
		onToggleButtonReplies(e) {
			this.loadingReplySettings = true

			this.mainStore.savePreference({
				key: 'reply-mode',
				value: e.target.checked ? 'bottom' : 'top',
			})
				.catch((error) => Logger.error('could not save preferences', { error }))
				.then(() => {
					this.loadingReplySettings = false
				})
		},
		onToggleExternalAvatars(e) {
			this.loadingAvatarSettings = true

			this.mainStore.savePreference({
				key: 'external-avatars',
				value: e.target.checked ? 'true' : 'false',
			})
				.catch((error) => Logger.error('could not save preferences', { error }))
				.then(() => {
					this.loadingAvatarSettings = false
				})
		},
		async onToggleSearchPriorityBody(e) {
			this.loadingPrioritySettings = true
			try {
				await this.mainStore.savePreference({
					key: 'search-priority-body',
					value: e.target.checked ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('could not save preferences', { error })
			} finally {
				this.loadingPrioritySettings = false
			}
		},
		onToggleCollectData(e) {
			this.loadingOptOutSettings = true

			this.mainStore.savePreference({
				key: 'collect-data',
				value: e.target.checked ? 'true' : 'false',
			})
				.catch((error) => Logger.error('could not save preferences', { error }))
				.then(() => {
					this.loadingOptOutSettings = false
				})
		},
		async onSortByDate(e) {
			const previousValue = this.sortOrder
			try {
				this.sortOrder = e
				await this.mainStore.savePreference({
					key: 'sort-order',
					value: e,
				})
				this.mainStore.removeAllEnvelopesMutation()
			} catch (error) {
				Logger.error('could not save preferences', { error })
				this.sortOrder = previousValue
				showError(t('mail', 'Could not update preference'))
			}
		},
		async onToggleAutoTagging(e) {
			this.toggleAutoTagging = true

			try {
				await this.mainStore.savePreference({
					key: 'tag-classified-messages',
					value: e.target.checked ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('could not save preferences', { error })

				showError(t('mail', 'Could not update preference'))
			} finally {
				this.toggleAutoTagging = false
			}
		},
		async onToggleFollowUpReminders(e) {
			try {
				await this.mainStore.savePreference({
					key: 'follow-up-reminders',
					value: e.target.checked ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
				showError(t('mail', 'Could not update preference'))
			}
		},
		async onToggleInternalAddress(e) {
			try {
				await this.mainStore.savePreference({
					key: 'internal-addresses',
					value: e.target.checked ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
				showError(t('mail', 'Could not update preference'))
			}
		},
		registerProtocolHandler() {
			if (window.navigator.registerProtocolHandler) {
				const url
					= window.location.protocol + '//' + window.location.host + generateUrl('apps/mail/compose?uri=%s')
				try {
					window.navigator.registerProtocolHandler('mailto', url, OC.theme.name + ' Mail')
				} catch (err) {
					Logger.error('could not register protocol handler', { err })
				}
			}
		},
		mailvelopeAuthorizeDomain() {
			const iframe = document.createElement('iframe')
			iframe.style = 'display: none'
			iframe.src = 'https://api.mailvelope.com/authorize-domain/?api=true'
			document.body.append(iframe)
		},
	},
}
</script>

<style lang="scss" scoped>
p.app-settings span.loading-icon {
	display: inline-block;
	vertical-align: middle;
	padding: 5px 0;
}
p.app-settings {
	padding: 10px 0;
}
.app-settings-button {
	display: inline-flex;
	background-position: 10px center;
	text-align: left;
	margin-top: 6px;
}
.app-settings-button.button.primary.new-button {
	color: var(--color-primary-element-text);
	//this style will be removed after we migrate also the  'add mail account' to material design
	padding-left: 34px;
	gap: 4px;
	width: fit-content;
}
.app-settings-link {
	text-decoration: underline;
}
:deep(.button-vue__text) {
	text-overflow: clip;
	white-space: normal;
}
:deep(.button-vue__wrapper) {
	justify-content: flex-start;
}
.mailvelope-section {
	padding-top: 15px;

	a.button {
		display: flex;
		align-items: center;
		line-height: normal;
		min-height: 44px;
		font-size: unset;

		&:focus-visible,
		&:hover {
			box-shadow: 0 0 0 1px var(--color-primary-element);
		}
	}
}
.material-design-icon {
	&.lock-icon {
		margin-right: 10px;
	}

}
.section-title {
	margin-top: 20px;
	margin-bottom: 10px;
}
.sorting {
	display: flex;
	width: 100%;
	&__switch{
		width: 50%;
	}
}
.mail-creation-button {
	width: 100%;
}
.settings-hint {
	margin-top: -12px;
	margin-bottom: 6px;
	color: var(--color-text-maxcontrast);
}
.app-settings-section {
	list-style: none;
}
// align it with the checkbox
.internal_address{
	margin-left: 3px;
}
</style>
