<template>
	<div id="app-content-wrapper">
		<Loading v-if="loading"
				 :hint="t('mail', 'Loading messages')"/>
		<template v-else>
			<EnvelopeList :account="account"
						  :folder="folder"
						  :envelopes="envelopes"/>
			<NewMessageDetail v-if="newMessage"/>
			<Message v-else-if="hasMessages"/>
		</template>
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
		props: {
			account: {
				type: Object,
				required: true,
			},
			folder: {
				type: Object,
				required: true,
			},
		},
		data () {
			return {
				loading: true,
			}
		},
		computed: {
			hasMessages () {
				return this.$store.getters.getEnvelopes(
					this.account.id,
					this.folder.id,
				).length > 0
			},
			newMessage () {
				return this.$route.params.messageUid === 'new'
			},
			envelopes () {
				return this.$store.getters.getEnvelopes(
					this.account.id,
					this.folder.id,
				)
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
						accountId: this.account.id,
						folderId: this.folder.id,
					}).then(envelopes => {
					this.loading = false;

					if (this.$route.name !== 'message' && envelopes.length > 0) {
						// Show first message
						let first = envelopes[0];

						// Keep the selected account-folder combination, but navigate to the message
						// (it's not a bug that we don't use first.accountId and first.folderId here)
						this.$router.replace({
							name: 'message',
							params: {
								accountId: this.account.id,
								folderId: this.folder.id,
								messageUid: first.uid,
							}
						})
					}
				});
			}
		}
	}
</script>
