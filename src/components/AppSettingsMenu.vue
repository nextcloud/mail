<template>
	<div>
		<router-link to="/setup" class="icon-add app-settings-button button new-button">{{ t('mail', 'Add mail account') }}</router-link>

		<p v-if="loadingAvatarSettings" class="app-settings avatar-settings">
			<span class="icon-loading-small"></span>
			{{ t('mail', 'Use Gravatar and favicon avatars') }}
		</p>
		<p v-else class="app-settings">
			<input
				id="gravatar-enabled"
				class="checkbox"
				type="checkbox"
				:checked="useExternalAvatars"
				@change="onToggleExternalAvatars"
			/>
			<label for="gravatar-enabled">{{ t('mail', 'Use Gravatar and favicon avatars') }}</label>
		</p>

		<p class="app-settings-hint">
			<router-link :to="{name: 'keyboardShortcuts'}">
				{{ t('mail', 'Keyboard shortcuts') }}
			</router-link>
		</p>

		<p class="app-settings-hint">
			<a href="https://www.mailvelope.com/" target="_blank">{{
				t('mail', 'Looking for a way to encrypt your emails? Install the Mailvelope browser extension!')
			}}</a>
		</p>
	</div>
</template>

<script>
import Logger from '../logger'

export default {
	name: 'AppSettingsMenu',
	data() {
		return {
			loadingAvatarSettings: false,
		}
	},
	computed: {
		useExternalAvatars() {
			return this.$store.getters.getPreference('external-avatars', 'true') === 'true'
		},
	},
	methods: {
		onToggleExternalAvatars(e) {
			this.loadingAvatarSettings = true

			this.$store
				.dispatch('savePreference', {
					key: 'external-avatars',
					value: e.target.checked ? 'true' : 'false',
				})
				.catch(error => Logger.error('could not save preferences', {error}))
				.then(() => {
					this.loadingAvatarSettings = false
				})
		},
	},
}
</script>

<style scoped>
p.avatar-settings span.icon-loading-small {
	display: inline-block;
	vertical-align: middle;
	padding: 5px 0;
}
p.app-settings {
	padding-top: 15px;
}
.app-settings-button {
	display: block;
	padding-left: 34px;
	background-position: 10px center;
}
</style>
