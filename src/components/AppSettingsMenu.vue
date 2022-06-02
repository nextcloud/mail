<template>
	<div>
		<router-link to="/setup" class="icon-add-white app-settings-button button primary new-button">
			{{ t('mail', 'Add mail account') }}
		</router-link>

		<p v-if="loadingOptOutSettings" class="app-settings">
			<span class="icon-loading-small" />
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
			<span class="icon-loading-small" />
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
			<span class="icon-loading-small" />
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
			<span class="icon-loading-small" />
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
			<button class="icon-mail app-settings-button" @click="registerProtocolHandler">
				{{ t('mail', 'Register as application for mail links') }}
			</button>
		</p>

		<button
			class="icon-details app-settings-button"
			@click.prevent.stop="showKeyboardShortcuts"
			@shortkey="toggleKeyboardShortcuts">
			{{ t('mail', 'Show keyboard shortcuts') }}
		</button>
		<KeyboardShortcuts v-if="displayKeyboardShortcuts" @close="closeKeyboardShortcuts" />

		<p class="mailvelope-section">
			{{ t('mail', 'Looking for a way to encrypt your emails?') }}

			<a
				class="icon-password button app-settings-button"
				href="https://www.mailvelope.com/"
				target="_blank"
				rel="noopener noreferrer">
				{{ t('mail', 'Install Mailvelope browser extension') }}
			</a>
		</p>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import Logger from '../logger'
import KeyboardShortcuts from '../views/KeyboardShortcuts'

export default {
	name: 'AppSettingsMenu',
	components: {
		KeyboardShortcuts,
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
p.app-settings span.icon-loading-small {
	display: inline-block;
	vertical-align: middle;
	padding: 5px 0;
}
p.app-settings {
	padding: 10px 0;
}
.app-settings-button {
	display: block;

	padding-left: 34px;
	background-position: 10px center;
	text-align: left;
}
.app-settings-button.button.primary.new-button {
	color: var(--color-main-background);
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
		min-height: 34px;
	}
}
</style>
