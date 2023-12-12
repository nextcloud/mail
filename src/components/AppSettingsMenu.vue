<template>
	<div class="app-settings">
		<NcAppSettingsDialog id="app-settings-dialog"
			:title="t('mail', 'Mail settings')"
			:show-navigation="true"
			:open.sync="showSettings">
			<NcAppSettingsSection id="account-settings" :title="t('mail', 'Account creation')">
				<NcButton v-if="allowNewMailAccounts"
					type="primary"
					to="/setup"
					:aria-label="t('mail', 'Add mail account')"
					class="app-settings-button">
					<template #icon>
						<IconAdd :size="20" />
					</template>
					{{ t('mail', 'Add mail account') }}
				</NcButton>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="body-settings" :title="t('mail', 'Activate body search')">
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
			</NcAppSettingsSection>
			<NcAppSettingsSection id="data-settings" :title="t('mail', 'Data collection consent')">
				<p class="settings-hint">
					{{ t('mail', 'Allow the app to collect data about your interactions. Based on this data, the app will adapt to your preferences. The data will only be stored locally.') }}
				</p>
				<p v-if="loadingOptOutSettings" class="app-settings">
					<IconLoading :size="20" />
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
			</NcAppSettingsSection>
			<NcAppSettingsSection id="autotagging-settings" :title="t('mail', 'Auto tagging text')">
				<p v-if="toggleAutoTagging" class="app-settings">
					<IconLoading :size="20" />
					{{ autoTaggingText }}
				</p>
				<p v-else class="app-settings">
					<input id="auto-tagging-toggle"
						class="checkbox"
						type="checkbox"
						:checked="useAutoTagging"
						@change="onToggleAutoTagging">
					<label for="auto-tagging-toggle">{{ autoTaggingText }}</label>
				</p>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="trusted-sender" :title="t('mail', 'Trusted senders')">
				<TrustedSenders />
			</NcAppSettingsSection>
			<NcAppSettingsSection id="gavatar-settings" :title="t('mail', 'Gavatar settings')">
				<p v-if="loadingAvatarSettings" class="app-settings avatar-settings">
					<IconLoading :size="20" />
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
			</NcAppSettingsSection>
			<NcAppSettingsSection id="reply-settings" :title="t('mail', 'Reply text position')">
				<p v-if="loadingReplySettings" class="app-settings reply-settings">
					<IconLoading :size="20" />
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
			</NcAppSettingsSection>
			<NcAppSettingsSection id="mailto-settings" :title="t('mail', 'Mailto')">
				<p class="settings-hint">
					{{ t('mail', 'Register as application for mail links') }}
				</p>
				<NcButton type="secondary"
					class="app-settings-button"
					:aria-label="t('mail', 'Register as application for mail links')"
					@click="registerProtocolHandler">
					<template #icon>
						<IconEmail :size="20" />
					</template>
					{{ t('mail', 'Register') }}
				</NcButton>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="keyboard-settings" :title="t('mail', 'Keyboard')">
				<NcButton class="app-settings-button"
					type="secondary"
					:aria-label="t('mail', 'Show keyboard shortcuts')"
					@click.prevent.stop="showKeyboardShortcuts"
					@shortkey="toggleKeyboardShortcuts">
					<template #icon>
						<IconInfo :size="20" />
					</template>
					{{ t('mail', 'Show keyboard shortcuts') }}
				</NcButton>
				<KeyboardShortcuts v-if="displayKeyboardShortcuts" @close="closeKeyboardShortcuts" />
			</NcAppSettingsSection>
			<NcAppSettingsSection id="smime-settings" :title="t('mail', 'S/MIME')">
				<NcButton class="app-settings-button"
					type="secondary"
					:aria-label="t('mail', 'Manage S/MIME certificates')"
					@click.prevent.stop="displaySmimeCertificateModal = true">
					<template #icon>
						<IconLock :size="20" />
					</template>
					{{ t('mail', 'Manage S/MIME certificates') }}
				</NcButton>
				<SmimeCertificateModal v-if="displaySmimeCertificateModal"
					@close="displaySmimeCertificateModal = false" />
			</NcAppSettingsSection>
			<NcAppSettingsSection id="sorting-settings" :title="t('mail', 'Sorting')">
				<div class="sorting">
					<CheckboxRadioSwitch class="sorting__switch"
						:checked="sortOrder"
						value="newest"
						name="order_radio"
						type="radio"
						@update:checked="onSortByDate">
						{{ t('mail', 'Newest') }}
					</CheckboxRadioSwitch>
					<CheckboxRadioSwitch class="sorting__switch"
						:checked="sortOrder"
						value="oldest"
						name="order_radio"
						type="radio"
						@update:checked="onSortByDate">
						{{ t('mail', 'Oldest') }}
					</CheckboxRadioSwitch>
				</div>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="mailvelope-settings" :title="t('mail', 'Mailvelope')">
				<p class="mailvelope-section">
					{{ t('mail', 'Looking for a way to encrypt your emails?') }}
				</p>
				<a href="https://www.mailvelope.com/"
					target="_blank"
					rel="noopener noreferrer">
					{{ t('mail', 'Install Mailvelope browser extension by clicking here') }}
				</a>
			</NcAppSettingsSection>
		</NcAppSettingsDialog>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import { NcAppSettingsSection, NcAppSettingsDialog, NcButton, NcLoadingIcon as IconLoading, NcCheckboxRadioSwitch as CheckboxRadioSwitch } from '@nextcloud/vue'

import IconInfo from 'vue-material-design-icons/Information.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconEmail from 'vue-material-design-icons/Email.vue'
import IconLock from 'vue-material-design-icons/Lock.vue'
import Logger from '../logger.js'
import KeyboardShortcuts from '../views/KeyboardShortcuts.vue'
import SmimeCertificateModal from './smime/SmimeCertificateModal.vue'
import TrustedSenders from './TrustedSenders.vue'

export default {
	name: 'AppSettingsMenu',
	components: {
		TrustedSenders,
		NcButton,
		KeyboardShortcuts,
		IconInfo,
		IconEmail,
		IconAdd,
		IconLoading,
		IconLock,
		SmimeCertificateModal,
		CheckboxRadioSwitch,
		NcAppSettingsDialog,
		NcAppSettingsSection,
	},
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
			displayKeyboardShortcuts: false,
			// eslint-disable-next-line
			autoTaggingText: t('mail', 'Automatically classify importance of new email'),
			toggleAutoTagging: false,
			displaySmimeCertificateModal: false,
			sortOrder: 'newest',
			showSettings: false,
		}
	},
	computed: {
		searchPriorityBody() {
			return this.$store.getters.getPreference('search-priority-body', 'false') === 'true'
		},
		useBottomReplies() {
			return this.$store.getters.getPreference('reply-mode', 'top') === 'bottom'
		},
		useExternalAvatars() {
			return this.$store.getters.getPreference('external-avatars', 'true') === 'true'
		},
		useDataCollection() {
			return this.$store.getters.getPreference('collect-data', 'true') === 'true'
		},
		useAutoTagging() {
			return this.$store.getters.getPreference('tag-classified-messages', 'true') === 'true'
		},
		allowNewMailAccounts() {
			return this.$store.getters.getPreference('allow-new-accounts', true)
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
		this.sortOrder = this.$store.getters.getPreference('sort-order', 'newest')
	},
	methods: {
		async onOpen() {
			this.showSettings = true
		},
		onToggleButtonReplies(e) {
			this.loadingReplySettings = true

			this.$store
				.dispatch('savePreference', {
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

			this.$store
				.dispatch('savePreference', {
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
				await this.$store
					.dispatch('savePreference', {
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

			this.$store
				.dispatch('savePreference', {
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
				await this.$store
					.dispatch('savePreference', {
						key: 'sort-order',
						value: e,
					})
				this.$store.commit('removeAllEnvelopes')

			} catch (error) {
				Logger.error('could not save preferences', { error })
				this.sortOrder = previousValue
				showError(t('mail', 'Could not update preference'))
			}
		},
		async onToggleAutoTagging(e) {
			this.toggleAutoTagging = true

			try {
				await this.$store
					.dispatch('savePreference', {
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
		/**
		 * Show the keyboard shortcuts overview
		 */
		showKeyboardShortcuts() {
			this.displayKeyboardShortcuts = true
		},
		/**
		 * Hide the keyboard shortcuts overview
		 */
		closeKeyboardShortcuts() {
			this.displayKeyboardShortcuts = false
		},
		/**
		 * Toggles the keyboard shortcuts overview
		 */
		toggleKeyboardShortcuts() {
			this.displayKeyboardShortcuts = !this.displayKeyboardShortcuts
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
	width: 100%;
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
::v-deep .button-vue__text {
	text-overflow: clip;
	white-space: normal;
}
::v-deep .button-vue__wrapper {
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
</style>
