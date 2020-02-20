<template>
	<AppContent>
		<AppDetailsToggle v-if="showMessage" @close="hideMessage" />
		<div id="app-content-wrapper">
			<AppContentList
				v-infinite-scroll="onScroll"
				v-shortkey.once="shortkeys"
				:show-details="showMessage"
				:infinite-scroll-disabled="false"
				:infinite-scroll-distance="10"
				@shortkey.native="onShortcut"
			>
				<Mailbox :account="account" :folder="folder" :search-query="searchQuery" :bus="bus" />
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
import Vue from 'vue'

import AppDetailsToggle from './AppDetailsToggle'
import Mailbox from './Mailbox'
import Message from './Message'
import NewMessageDetail from './NewMessageDetail'
import NoMessageSelected from './NoMessageSelected'
import {normalizeEnvelopeListId} from '../store/normalization'

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
		hasMessages() {
			// it actually should be `return this.$store.getters.getEnvelopes(this.account.id, this.folder.id).length > 0`
			// but for some reason Vue doesn't track the dependencies on reactive data then and messages in subfolders can't
			// be opened then
			const list = this.folder.envelopeLists[normalizeEnvelopeListId(this.searchQuery)]

			if (list === undefined) {
				return false
			}
			return list.length > 0
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
				},
			})
		},
		deleteMessage({envelope, message}) {
			console.info({envelope, message})
			this.bus.$emit('delete', {envelope, message})
		},
		onScroll() {
			this.bus.$emit('loadMore')
		},
		onShortcut(e) {
			this.bus.$emit('shortcut', e)
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
