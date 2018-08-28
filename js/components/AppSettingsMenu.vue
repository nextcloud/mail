<template>
	<div>
		<router-link to="/setup"
					 id="new-mail-account"
					 class="button new-button">{{ t('mail', 'Add mail account') }}</router-link>
		<p>
			<input class="checkbox"
				   id="gravatar-enabled"
				   type="checkbox">
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
		name: "AppSettingsMenu"
	}
</script>
