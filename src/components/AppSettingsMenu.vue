<template>
	<div>
		<router-link v-if="allowNewMailAccounts" to="/setup" class="app-settings-button button primary new-button">
			<IconAdd :size="20" />
			{{ t('mail', 'Add mail account') }}
		</router-link>

		<p v-if="loadingOptOutSettings" class="app-settings">
			<IconLoading :size="20" />
			{{ optOutSettingsText }}
		</p>
		<p v-else class="app-settings">
			<input
				id="data-collection-toggle"
				class="checkbox"
				type="checkbox"
				:checked="useDataCollection"
				@change="onToggleCollectData">
			<label for="data-collection-toggle">{{ optOutSettingsText }}</label>
		</p>

		<p v-if="toggleAutoTagging" class="app-settings">
			<IconLoading :size="20" />
			{{ autoTaggingText }}
		</p>
		<p v-else class="app-settings">
			<input
				id="auto-tagging-toggle"
				class="checkbox"
				type="checkbox"
				:checked="useAutoTagging"
				@change="onToggleAutoTagging">
			<label for="auto-tagging-toggle">{{ autoTaggingText }}</label>
		</p>

		<p v-if="loadingAvatarSettings" class="app-settings avatar-settings">
			<IconLoading :size="20" />
			{{ t('mail', 'Use Gravatar and favicon avatars') }}
		</p>
		<p v-else class="app-settings">
			<input
				id="gravatar-enabled"
				class="checkbox"
				type="checkbox"
				:checked="useExternalAvatars"
				@change="onToggleExternalAvatars">
			<label for="gravatar-enabled">{{ t('mail', 'Use Gravatar and favicon avatars') }}</label>
		</p>

		<p v-if="loadingReplySettings" class="app-settings reply-settings">
			<IconLoading :size="20" />
			{{ replySettingsText }}
		</p>
		<p v-else class="app-settings">
			<input
				id="bottom-reply-enabled"
				class="checkbox"
				type="checkbox"
				:checked="useBottomReplies"
				@change="onToggleButtonReplies">
			<label for="bottom-reply-enabled">{{ replySettingsText }}</label>
		</p>

		<p>
			<ButtonVue type="secondary" class="app-settings-button" @click="registerProtocolHandler">
				<template #icon>
					<IconEmail :size="20" />
				</template>
				{{ t('mail', 'Register as application for mail links') }}
			</ButtonVue>
		</p>

		<ButtonVue
			class="app-settings-button"
			type="secondary"
			@click.prevent.stop="showKeyboardShortcuts"
			@shortkey="toggleKeyboardShortcuts">
			<template #icon>
				<IconInfo :size="20" />
			</template>
			{{ t('mail', 'Show keyboard shortcuts') }}
		</ButtonVue>
		<KeyboardShortcuts v-if="displayKeyboardShortcuts" @close="closeKeyboardShortcuts" />

		<ButtonVue class="app-settings-button"
			type="secondary"
			@click.prevent.stop="displaySmimeCertificateModal = true">
			<template #icon>
				<IconLock :size="20" />
			</template>
			{{ t('mail', 'Manage S/MIME certificates') }}
		</ButtonVue>
		<SmimeCertificateModal v-if="displaySmimeCertificateModal"
			@close="displaySmimeCertificateModal = false" />

		<p class="mailvelope-section">
			{{ t('mail', 'Looking for a way to encrypt your emails?') }}
		</p>
		<a
			href="https://www.mailvelope.com/"
			target="_blank"
			rel="noopener noreferrer">
			{{ t('mail', 'Install Mailvelope browser extension here') }}
		</a>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'

import IconInfo from 'vue-material-design-icons/Information'
import IconAdd from 'vue-material-design-icons/Plus'
import IconEmail from 'vue-material-design-icons/Email'
import IconLock from 'vue-material-design-icons/Lock'
import Logger from '../logger'
import KeyboardShortcuts from '../views/KeyboardShortcuts'
import SmimeCertificateModal from './smime/SmimeCertificateModal'

export default {
	name: 'AppSettingsMenu',
	components: {
		ButtonVue,
		KeyboardShortcuts,
		IconInfo,
		IconEmail,
		IconAdd,
		IconLoading,
		IconLock,
		SmimeCertificateModal,
	},
	data() {
		return {
			loadingAvatarSettings: false,
			// eslint-disable-next-line
			optOutSettingsText: t('mail', 'Allow the app to collect data about your interactions. Based on this data, the app will adapt to your preferences. The data will only be stored locally.'),
			loadingOptOutSettings: false,
			// eslint-disable-next-line
			replySettingsText: t('mail', 'Put my text to the bottom of a reply instead of on top of it.'),
			loadingReplySettings: false,
			displayKeyboardShortcuts: false,
			// eslint-disable-next-line
			autoTaggingText: t('mail', 'Automatically classify importance of new email'),
			toggleAutoTagging: false,
			displaySmimeCertificateModal: false,
		}
	},
	computed: {
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
	methods: {
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
}
.app-settings-button.button.primary.new-button {
	color: var(--color-main-background);
	//this style will be removed after we migrate also the  'add mail account' to material design
	padding-left: 34px;
	gap: 4px;
}
.app-settings-link {
	text-decoration: underline;
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
	opacity: .7;
	&.lock-icon {
		margin-right: 10px;
	}

}
</style>
