<template>
	<NcEmptyContent
		:title="t('mail', 'Pending or not sent messages will show up here')">
		<template #icon>
			<IconMail :size="65" />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/Email'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent'

export default {
	name: 'OutboxMessageContent',
	components: {
		IconMail,
		NcEmptyContent,
	},
}
</script>

<style scoped>

</style>
