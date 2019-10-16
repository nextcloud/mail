<template>
	<AppContent>
		<AppDetailsToggle v-if="showMessage" @close="hideMessage" />
		<div id="app-content-wrapper">
			<Loading v-if="loading" :hint="t('mail', 'Loading messages')" />
			<template v-else>
				<EnvelopeList
					:account="account"
					:folder="folder"
					:envelopes="envelopes"
					:search-query="searchQuery"
					:show="!showMessage"
				/>
				<NewMessageDetail v-if="newMessage" />
				<Message v-else-if="showMessage" />
				<NoMessageSelected v-else-if="hasMessages" :mailbox="folder.name" />
			</template>
		</div>
	</AppContent>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'

import AppDetailsToggle from './AppDetailsToggle'
import EnvelopeList from './EnvelopeList'
import Loading from './Loading'
import Logger from '../logger'
import Message from './Message'
import NewMessageDetail from './NewMessageDetail'
import NoMessageSelected from './NoMessageSelected'

export default {
	name: 'FolderContent',
	components: {
		AppContent,
		AppDetailsToggle,
		EnvelopeList,
		Loading,
		Message,
		NewMessageDetail,
		NoMessageSelected,
	},
	mixins: [isMobile],
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
			// it actually should be `return this.$store.getters.getEnvelopes(this.account.id, this.folder.id).length > 0`
			// but for some reason Vue doesn't track the dependencies on reactive data then and messages in subfolders can't
			// be opened then

			return this.folder.envelopes.map(msgId => this.$store.state.envelopes[msgId])
		},
		showMessage() {
			return this.hasMessages && this.$route.name === 'message'
		},
		newMessage() {
			return (
				this.$route.params.messageUid === 'new' ||
				this.$route.params.messageUid === 'reply' ||
				this.$route.params.messageUid === 'replyAll'
			)
		},
		envelopes() {
			if (this.searchQuery === undefined) {
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
					Logger.debug('envelopes fetched', envelopes)

					this.loading = false

					if (!this.isMobile && this.$route.name !== 'message' && envelopes.length > 0) {
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
		hideMessage() {
			this.$router.replace({
				name: 'folder',
				params: {
					accountId: this.account.id,
					folderId: this.folder.id,
				},
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
