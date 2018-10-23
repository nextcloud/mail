<template>
	<div id="app-content">
		<div id="app-content-wrapper">
			<Loading v-if="loading"
					 :hint="t('mail', 'Loading messages')"/>
			<template v-else>
				<EnvelopeList/>
				<NewMessageDetail v-if="newMessage"/>
				<Message v-else-if="hasMessages"/>
			</template>
		</div>
	</div>
</template>

<script>
	import Message from "./Message";
	import EnvelopeList from "./EnvelopeList";
	import NewMessageDetail from "./NewMessageDetail";
	import Loading from "./Loading";

	export default {
		name: "FolderContent",
		components: {
			Loading,
			NewMessageDetail,
			Message,
			EnvelopeList,
		},
		data () {
			return {
				loading: true,
			}
		},
		computed: {
			hasMessages () {
				return this.$store.getters.getEnvelopes(
					this.$route.params.accountId,
					this.$route.params.folderId
				).length > 0
			},
			newMessage () {
				return this.$route.params.messageId === 'new'
			}
		},
		created () {
			this.fetchData()
		},
		watch: {
			'$route' (to, from) {
				if (to.name === 'folder') {
					// Navigate (back) to the folder view -> (re)fetch data
					this.fetchData()
				}
			}
		},
		methods: {
			fetchData () {
				this.loading = true

				this.$store.dispatch(
					'fetchEnvelopes', {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId
					}).then(envelopes => {
					this.loading = false;

					if (this.$route.name !== 'message' && envelopes.length > 0) {
						// Show first message
						let first = envelopes[0];

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
	}
</script>
