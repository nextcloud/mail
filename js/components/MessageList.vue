<template>
	<div class="app-content-list">
		<MessageListItem v-for="env in envelopes"
						 :key="env.id"
						 :from="env.from"
						 :subject="env.subject"/>
	</div>
</template>

<script>
	import MessageListItem from "./MessageListItem";

	export default {
		name: "MessageList",
		computed: {
			envelopes() {
				return this.$store.getters.getEnvelopes(
					this.$route.params.accountId,
					this.$route.params.folderId
				)
			}
		},
		components: {MessageListItem}
	}
</script>
