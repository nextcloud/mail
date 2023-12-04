<template>
	<NcEmptyContent
		:title="t('mail', 'No messages in this mailbox')">
		<template #icon>
			<IconMail :size="65" />
		</template>
	</NcEmptyContent>
</template>

<script>
import IconMail from 'vue-material-design-icons/Email.vue'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

export default {
	name: 'EmptyMailbox',
	components: {
		IconMail,
		NcEmptyContent,
	},
}
</script>
