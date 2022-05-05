<template>
	<div id="emptycontent">
		<div class="icon-mail" />
		<h2>{{ t('mail', 'Pending or not sent messages will show up here') }}</h2>
		<div />
	</div>
</template>

<script>
export default {
	name: 'OutboxMessageContent',
	components: {
	},
}
</script>

<style scoped>

</style>
