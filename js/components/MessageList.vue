<template>
	<div class="app-content-list">
		<MessageListItem v-for="message in messages"
						 :key="message.id"
						 :from="message.from"
						 :subject="message.subject"/>
	</div>
</template>

<script>
	import MessageListItem from "./MessageListItem";

	export default {
		name: "MessageList",
		computed: {
			messages () {
				return this.$store.getters.currentFolder.messages
			}
		},
		components: {MessageListItem}
	}
</script>
