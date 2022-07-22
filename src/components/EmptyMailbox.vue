<template>
	<div id="emptycontent">
		<IconMail :size="65" />
		<h2>{{ t('mail', 'No messages in this mailbox') }}</h2>
	</div>
</template>

<script>
import IconMail from 'vue-material-design-icons/Email'
export default {
	name: 'EmptyMailbox',
	components: {
		IconMail,
	},
}
</script>
