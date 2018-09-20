<template>
	<div class="app-content-list">
		<EmptyFolder v-if="envelopes.length === 0"/>
		<MessageListItem v-else
						 v-for="env in envelopes"
						 :key="env.id"
						 :data="env"/>
	</div>
</template>

<script>
	import EmptyFolder from "./EmptyFolder";
	import MessageListItem from "./MessageListItem";

	export default {
		name: "MessageList",
		computed: {
			envelopes () {
				return this.$store.getters.getEnvelopes(
					this.$route.params.accountId,
					this.$route.params.folderId
				)
			}
		},
		components: {
			MessageListItem,
			EmptyFolder
		}
	}
</script>
