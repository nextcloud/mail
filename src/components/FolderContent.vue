<template>
	<div id="app-content-wrapper">
		<Loading v-if="loading" :hint="t('mail', 'Loading messages')" />
		<template v-else>
			<EnvelopeList :account="account" :folder="folder" :envelopes="envelopes" :search-query="searchQuery" />
			<NewMessageDetail v-if="newMessage" />
			<Message v-else-if="hasMessages" />
		</template>
	</div>
</template>

<script>
import _ from 'lodash'

import Message from './Message'
import EnvelopeList from './EnvelopeList'
import NewMessageDetail from './NewMessageDetail'
import Loading from './Loading'

export default {
	name: 'FolderContent',
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
	data() {
		return {
			loading: true,
			searchQuery: undefined,
			alive: false,
		}
	},
	computed: {
		hasMessages() {
			return this.$store.getters.getEnvelopes(this.account.id, this.folder.id).length > 0
		},
		newMessage() {
			return this.$route.params.messageUid === 'new'
		},
		envelopes() {
			if (_.isUndefined(this.searchQuery)) {
				return this.$store.getters.getEnvelopes(this.account.id, this.folder.id)
			} else {
				return this.$store.getters.getSearchEnvelopes(this.account.id, this.folder.id)
			}
		},
	},
	watch: {
		$route(to, from) {
			if (to.name === 'folder') {
				// Navigate (back) to the folder view -> (re)fetch data
				this.fetchData()
			}
		},
	},
	created() {
		this.alive = true

		new OCA.Search(this.searchProxy, this.clearSearchProxy)

		this.fetchData()
	},
	beforeDestroy() {
		this.alive = false
	},
	methods: {
		fetchData() {
			this.loading = true

			this.$store
				.dispatch('fetchEnvelopes', {
					accountId: this.account.id,
					folderId: this.folder.id,
					query: this.searchQuery,
				})
				.then(() => {
					const envelopes = this.envelopes
					console.debug('envelopes fetched', envelopes)

					this.loading = false

					if (this.$route.name !== 'message' && envelopes.length > 0) {
						// Show first message
						let first = envelopes[0]

						// Keep the selected account-folder combination, but navigate to the message
						// (it's not a bug that we don't use first.accountId and first.folderId here)
						this.$router.replace({
							name: 'message',
							params: {
								accountId: this.account.id,
								folderId: this.folder.id,
								messageUid: first.uid,
							},
						})
					}
				})
		},
		searchProxy(query) {
			if (this.alive) {
				this.search(query)
			}
		},
		clearSearchProxy() {
			if (this.alive) {
				this.clearSearch()
			}
		},
		search(query) {
			this.searchQuery = query

			this.fetchData()
		},
		clearSearch() {
			this.searchQuery = undefined
		},
	},
}
</script>
