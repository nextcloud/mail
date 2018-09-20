<template>
	<div class="app-content-list">
		<EmptyFolder v-if="messages.length === 0"/>
		<MessageListItem v-for="message in messages"
						 :key="message.id"
						 :from="message.from"
						 :subject="message.subject"/>
	</div>
</template>

<script>
	import EmptyFolder from "./EmptyFolder";
	import MessageListItem from "./MessageListItem";

	export default {
		name: "MessageList",
		computed: {
			messages () {
				return this.$store.getters.currentFolder.messages
			}
		},
		components: { MessageListItem, EmptyFolder }
	}
</script>
