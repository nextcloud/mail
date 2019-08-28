<template>
	<AppContentList :show-details="!show">
		<transition-group
			v-infinite-scroll="loadMore"
			v-scroll="onScroll"
			v-shortkey.once="shortkeys"
			name="list"
			infinite-scroll-disabled="loading"
			infinite-scroll-distance="30"
			@shortkey.native="handleShortcut"
		>
			<div id="list-refreshing" key="loading" class="icon-loading-small" :class="{refreshing: refreshing}" />
			<EmptyFolder v-if="envelopes.length === 0" key="empty" />
			<Envelope
				v-for="env in envelopes"
				v-else
				:key="env.uid"
				:data="env"
				:show-account-color="folder.isUnified"
				@delete="onEnvelopeDeleted"
			/>
			<div id="load-more-mail-messages" key="loadingMore" :class="{'icon-loading-small': loadingMore}" />
		</transition-group>
	</AppContentList>
</template>

<script>
import _ from 'lodash'
import AppContentList from 'nextcloud-vue/dist/Components/AppContentList'
import infiniteScroll from 'vue-infinite-scroll'
import vuescroll from 'vue-scroll'
import Vue from 'vue'

import EmptyFolder from './EmptyFolder'
import Envelope from './Envelope'
import Logger from '../logger'

Vue.use(vuescroll, {throttle: 600})

export default {
	name: 'EnvelopeList',
	components: {
		AppContentList,
		Envelope,
		EmptyFolder,
	},
	directives: {
		infiniteScroll,
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
		envelopes: {
			type: Array,
			required: true,
		},
		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},
		show: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			loadingMore: false,
			refreshing: false,
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
		isSearch() {
			return !_.isUndefined(this.searchQuery)
		},
	},
	methods: {
		loadMore() {
			this.loadingMore = true

			this.$store
				.dispatch('fetchNextEnvelopePage', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					query: this.searchQuery,
				})
				.catch(error => Logger.error('could not fetch next envelope page', {error}))
				.then(() => {
					this.loadingMore = false
				})
		},
		sync() {
			this.refreshing = true

			this.$store
				.dispatch('syncEnvelopes', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
				})
				.catch(error => Logger.error('could not sync envelopes', {error}))
				.then(() => {
					this.refreshing = false
				})
		},
		onEnvelopeDeleted(envelope) {
			const envelopes = this.envelopes
			const idx = this.envelopes.indexOf(envelope)
			if (idx === -1) {
				Logger.debug('envelope to delete does not exist in envelope list')
				return
			}

			let next
			if (idx === 0) {
				next = envelopes[idx + 1]
			} else {
				next = envelopes[idx - 1]
			}

			if (!next) {
				Logger.debug('no next/previous envelope, not navigating')
				return
			}

			// Keep the selected account-folder combination, but navigate to a different message
			// (it's not a bug that we don't use next.accountId and next.folderId here)
			this.$router.push({
				name: 'message',
				params: {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					messageUid: next.uid,
				},
			})
		},
		onScroll(e, p) {
			if (p.scrollTop === 0 && !this.refreshing) {
				return this.sync()
			}
		},
		handleShortcut(e) {
			const envelopes = this.envelopes
			const currentUid = this.$route.params.messageUid

			if (!currentUid) {
				Logger.debug('ignoring shortcut: no envelope selected')
				return
			}

			const current = envelopes.filter(e => e.uid == currentUid)
			if (current.length === 0) {
				Logger.debug('ignoring shortcut: currently displayed messages is not in current envelope list')
				return
			}

			const env = current[0]
			const idx = envelopes.indexOf(env)

			switch (e.srcKey) {
				case 'next':
				case 'prev':
					let next
					if (e.srcKey === 'next') {
						next = envelopes[idx + 1]
					} else {
						next = envelopes[idx - 1]
					}

					if (!next) {
						Logger.debug('ignoring shortcut: head or tail of envelope list reached', {
							envelopes,
							idx,
							srcKey: e.srcKey,
						})
						return
					}

					// Keep the selected account-folder combination, but navigate to a different message
					// (it's not a bug that we don't use next.accountId and next.folderId here)
					this.$router.push({
						name: 'message',
						params: {
							accountId: this.$route.params.accountId,
							folderId: this.$route.params.folderId,
							messageUid: next.uid,
						},
					})
					break
				case 'del':
					Logger.debug('deleting', {env})
					this.$store
						.dispatch('deleteMessage', env)
						.catch(error => Logger.error('could not delete envelope', {env, error}))

					break
				case 'flag':
					Logger.debug('flagging envelope via shortkey', {env})
					this.$store
						.dispatch('toggleEnvelopeFlagged', env)
						.catch(error => Logger.error('could not flag envelope via shortkey', {env, error}))
					break
				case 'refresh':
					Logger.debug('syncing envelopes via shortkey')
					if (!this.refreshing) {
						this.sync()
					}

					break
				case 'unseen':
					Logger.debug('marking message as seen/unseen via shortkey', {env})
					this.$store
						.dispatch('toggleEnvelopeSeen', env)
						.catch(error =>
							Logger.error('could not mark envelope as seen/unseen via shortkey', {env, error})
						)
					break
				default:
					Logger.warn('shortcut ' + e.srcKey + ' is unknown. ignoring.')
			}
		},
	},
}
</script>

<style scoped>
#load-more-mail-messages {
	margin: 10px auto;
	padding: 10px;
	margin-top: 50px;
	margin-bottom: 200px;
}

/* TODO: put this in core icons.css as general rule for buttons with icons */
#load-more-mail-messages.icon-loading-small {
	padding-left: 32px;
	background-position: 9px center;
}

#list-refreshing {
	overflow-y: hidden;
	min-height: 0;

	transition-property: all;
	transition-duration: 0.5s;
	transition-timing-function: ease-in;
}

#list-refreshing.refreshing {
	min-height: 32px;
}

.list-enter-active,
.list-leave-active {
	transition: all 1s;
}

.list-enter,
.list-leave-to {
	opacity: 0;
	height: 0px;
	transform: scaleY(0);
}
</style>
