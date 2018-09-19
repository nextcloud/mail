<template>
	<div id="app-content">
		<div id="app-content-wrapper">
			<Loading v-if="loading"
					 :hint="t('mail', 'Loading messages')"/>
			<template v-else>
				<MessageList/>
				<NewMessageDetail v-if="newMessage"/>
				<Message v-else/>
			</template>
		</div>
	</div>
</template>

<script>
	import Message from "./Message";
	import MessageList from "./MessageList";
	import NewMessageDetail from "./NewMessageDetail";
	import Loading from "./Loading";

	export default {
		name: "FolderContent",
		components: {
			Loading,
			NewMessageDetail,
			Message,
			MessageList
		},
		data () {
			return {
				loading: true,
				newMessage: true,
			}
		},
		created () {
			this.$store.dispatch('fetchAccounts').then(() => {
				this.loading = false;
			});
		}
	}
</script>
