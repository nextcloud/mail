<template>
	<div id="emptycontent">
		<IconMail :size="65" />
		<h2>{{ t('mail', 'Pending or not sent messages will show up here') }}</h2>
		<div />
	</div>
</template>

<script>
import IconMail from 'vue-material-design-icons/Email'

export default {
	name: 'OutboxMessageContent',
	components: {
		IconMail,
	},
}
</script>

<style scoped>

</style>
