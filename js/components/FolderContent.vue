<template>
	<div id="app-content">
		<div id="app-content-wrapper">
			<Loading v-if="loading"
					 :hint="t('mail', 'Loading messages')"/>
			<template v-else>
				<MessageList/>
				<NewMessageDetail v-if="newMessage"/>
				<Message v-else-if="message"/>
				<NoMessageInFolder v-else />
			</template>
		</div>
	</div>
</template>

<script>
	import Message from "./Message";
	import MessageList from "./MessageList";
	import NewMessageDetail from "./NewMessageDetail";
	import Loading from "./Loading";
	import NoMessageInFolder from "./NoMessageInFolder";

	export default {
		name: "FolderContent",
		components: {
			NoMessageInFolder,
			Loading,
			NewMessageDetail,
			Message,
			MessageList
		},
		data () {
			return {
				loading: true,
				newMessage: false,
				message: false,
			}
		},
		created () {
			this.$store.dispatch(
				'fetchEnvelopes', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId
				}).then(envelopes => {
				this.loading = false;

				if (envelopes.length > 0) {
					// Show first message
					let first = envelopes[0];

					this.message = true;

					this.$router.replace({
						name: 'message',
						params: {
							accountId: this.$route.params.accountId,
							folderId: this.$route.params.folderId,
							messageId: first.id,
						}
					})
				}
			});
		}
	}
</script>
