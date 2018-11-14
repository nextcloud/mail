<template>
	<div>
		<router-link to="/setup"
					 class="button new-button">{{ t('mail', 'Add mail account') }}
		</router-link>

		<p v-if="loadingAvatarSettings"
		   class="avatar-settings">
			<span class="icon-loading-small"></span>
			{{ t('mail', 'Use Gravatar and favicon avatars') }}
		</p>
		<p v-else>
			<input class="checkbox"
				   id="gravatar-enabled"
				   type="checkbox"
				   :checked="useExternalAvatars"
				   @change="onToggleExternalAvatars">
			<label for="gravatar-enabled">{{ t('mail', 'Use Gravatar and favicon avatars') }}</label>
		</p>

		<p class="app-settings-hint">
			<a id="keyboard-shortcuts"
			   href="">{{ t('mail','Keyboard shortcuts')}}</a>
		</p>
	</div>
</template>

<script>
	export default {
		name: "AppSettingsMenu",
		data () {
			return {
				loadingAvatarSettings: false,
			}
		},
		computed: {
			useExternalAvatars () {
				return this.$store.getters.getPreference('external-avatars', 'true') === 'true'
			}
		},
		methods: {
			onToggleExternalAvatars (e) {
				this.loadingAvatarSettings = true

				this.$store.dispatch('savePreference', {
					key: 'external-avatars',
					value: e.target.checked ? 'true' : 'false',
				})
					.catch(console.error.bind(this))
					.then(() => {
						this.loadingAvatarSettings = false
					})
			}
		}
	}
</script>

<style scoped>
	p.avatar-settings span.icon-loading-small {
		display: inline-block;
		vertical-align: middle;
		padding: 5px 0;
	}
</style>