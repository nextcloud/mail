<template>
	<AppContentDetails>
		<div
			class="
			icon
			icon-mail" />
		<h2>{{ t('mail', 'Pending or not sent messages will show up here') }}</h2>
		<p />
	</AppContentDetails>
</template>

<script>
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'

export default {
	name: 'OutboxNoSelectedMessage',
	components: {
		AppContentDetails,
	},
}
</script>

<style scoped>

</style>
