<template>
	<AppContent>
		<AppDetailsToggle v-if="showMessage" @close="hideMessage" />
		<div id="app-content-wrapper">
			<Error v-if="error" :error="t('mail', 'Could not open mailbox')" message="" />
			<Loading v-else-if="loadingEnvelopes" :hint="t('mail', 'Loading messages')" />
			<Loading
				v-else-if="loadingCacheInitialization"
				:hint="t('mail', 'Loading messages')"
				:slow-hint="t('mail', 'Indexing your messages. This can take a bit longer for larger mailboxes.')"
			/>
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
				<NoMessageSelected v-else-if="hasMessages && !isMobile" />
			</template>
		</div>
	</AppContent>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'

import AppDetailsToggle from './AppDetailsToggle'
import EnvelopeList from './EnvelopeList'
import Error from './Error'
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
		Error,
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
			error: false,
			loadingEnvelopes: true,
			loadingCacheInitialization: false,
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
				this.loadEnvelopes()
			}
		},
	},
	created() {
		this.alive = true

		new OCA.Search(this.searchProxy, this.clearSearchProxy)

		this.loadEnvelopes()
	},
	beforeDestroy() {
		this.alive = false
	},
	methods: {
		initializeCache() {
			this.loadingCacheInitialization = true
			this.error = false

			this.$store
				.dispatch('syncEnvelopes', {
					accountId: this.account.id,
					folderId: this.folder.id,
					init: true,
				})
				.then(() => {
					this.loadingCacheInitialization = false

					return this.loadEnvelopes()
				})
		},
		loadEnvelopes() {
			this.loadingEnvelopes = true
			this.error = false

			this.$store
				.dispatch('fetchEnvelopes', {
					accountId: this.account.id,
					folderId: this.folder.id,
					query: this.searchQuery,
				})
				.then(() => {
					const envelopes = this.envelopes
					Logger.debug('envelopes fetched', envelopes)

					this.loadingEnvelopes = false

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
				.catch(error => {
					if (error.name === 'MailboxNotCachedException') {
						this.loadingEnvelopes = false

						return this.initializeCache()
					}
					this.error = {}
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

			this.loadEnvelopes()
		},
		clearSearch() {
			this.searchQuery = undefined
		},
	},
}
</script>
