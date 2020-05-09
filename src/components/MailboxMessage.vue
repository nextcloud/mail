<template>
	<AppContent>
		<AppDetailsToggle v-if="showMessage" @close="hideMessage" />
		<div id="app-content-wrapper">
			<AppContentList
				v-infinite-scroll="onScroll"
				v-shortkey.once="shortkeys"
				infinite-scroll-immediate-check="false"
				:show-details="showMessage"
				:infinite-scroll-disabled="false"
				:infinite-scroll-distance="10"
				@shortkey.native="onShortcut"
			>
				<Mailbox
					v-if="!folder.isPriorityInbox"
					:account="account"
					:folder="folder"
					:search-query="query"
					:bus="bus"
				/>
				<template v-else>
					<SectionTitle class="app-content-list-item important" :name="t('mail', 'Important')" />
					<Mailbox
						class="nameimportant"
						:account="unifiedAccount"
						:folder="unifiedInbox"
						:paginate="false"
						:search-query="appendToSearch('is:important not:starred')"
						:is-priority-inbox="true"
						:bus="bus"
					/>
					<SectionTitle class="app-content-list-item starred" :name="t('mail', 'Favorites')" />
					<Mailbox
						class="namestarred"
						:account="unifiedAccount"
						:folder="unifiedInbox"
						:paginate="false"
						:search-query="appendToSearch('is:starred not:important')"
						:is-priority-inbox="true"
						:bus="bus"
					/>
					<SectionTitle class="app-content-list-item other" :name="t('mail', 'Other')" />
					<Mailbox
						class="nameother"
						:account="unifiedAccount"
						:folder="unifiedInbox"
						:open-first="false"
						:search-query="appendToSearch('not:starred not:important')"
						:is-priority-inbox="true"
						:bus="bus"
					/>
				</template>
			</AppContentList>
			<NewMessageDetail v-if="newMessage" />
			<Message v-else-if="showMessage" @delete="deleteMessage" />
			<NoMessageSelected v-else-if="hasMessages && !isMobile" />
		</div>
	</AppContent>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentList from '@nextcloud/vue/dist/Components/AppContentList'
import infiniteScroll from 'vue-infinite-scroll'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'
import SectionTitle from './SectionTitle'
import Vue from 'vue'

import AppDetailsToggle from './AppDetailsToggle'
import logger from '../logger'
import Mailbox from './Mailbox'
import Message from './Message'
import NewMessageDetail from './NewMessageDetail'
import NoMessageSelected from './NoMessageSelected'
import {normalizedEnvelopeListId} from '../store/normalization'
import {UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID} from '../store/constants'

export default {
	name: 'MailboxMessage',
	directives: {
		infiniteScroll,
	},
	components: {
		AppContent,
		AppContentList,
		AppDetailsToggle,
		Mailbox,
		Message,
		NewMessageDetail,
		NoMessageSelected,
		SectionTitle,
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
			alive: false,
			bus: new Vue(),
			searchQuery: undefined,
			shortkeys: {
				del: ['del'],
				flag: ['s'],
				next: ['arrowright'],
				prev: ['arrowleft'],
				refresh: ['r'],
				unseen: ['u'],
			},
		}
	},
	computed: {
		unifiedAccount() {
			return this.$store.getters.getAccount(UNIFIED_ACCOUNT_ID)
		},
		unifiedInbox() {
			return this.$store.getters.getFolder(UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID)
		},
		hasMessages() {
			// it actually should be `return this.$store.getters.getEnvelopes(this.account.id, this.folder.id).length > 0`
			// but for some reason Vue doesn't track the dependencies on reactive data then and messages in subfolders can't
			// be opened then
			const list = this.folder.envelopeLists[normalizedEnvelopeListId(this.searchQuery)]

			if (list === undefined) {
				return false
			}
			return list.length > 0
		},
		showMessage() {
			return (this.folder.isPriorityInbox === true || this.hasMessages) && this.$route.name === 'message'
		},
		query() {
			if (this.$route.params.filter === 'starred') {
				if (this.searchQuery) {
					return this.searchQuery + ' is:starred'
				}
				return 'is:starred'
			}
			return this.searchQuery
		},
		newMessage() {
			return (
				this.$route.params.messageUid === 'new' ||
				this.$route.params.messageUid === 'reply' ||
				this.$route.params.messageUid === 'replyAll'
			)
		},
	},
	created() {
		this.alive = true

		new OCA.Search(this.searchProxy, this.clearSearchProxy)
	},
	beforeDestroy() {
		this.alive = false
	},
	methods: {
		hideMessage() {
			this.$router.replace({
				name: 'folder',
				params: {
					accountId: this.account.id,
					folderId: this.folder.id,
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
			})
		},
		deleteMessage(envelopeUid) {
			this.bus.$emit('delete', envelopeUid)
		},
		onScroll(event) {
			logger.debug('scroll', {event})

			this.bus.$emit('loadMore')
		},
		onShortcut(e) {
			this.bus.$emit('shortcut', e)
		},
		appendToSearch(str) {
			if (this.searchQuery === undefined) {
				return str
			}
			return this.searchQuery + ' ' + str
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
		},
		clearSearch() {
			this.searchQuery = undefined
		},
	},
}
</script>

<style scoped></style>
