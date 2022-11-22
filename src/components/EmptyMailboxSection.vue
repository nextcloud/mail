<template>
	<NcEmptyContent :title="t('mail', 'No messages')">
		<template #icon>
			<IconMail :size="65" />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/Email'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent'
export default {
	name: 'EmptyMailboxSection',
	components: {
		IconMail,
		NcEmptyContent,
	},
}
</script>
