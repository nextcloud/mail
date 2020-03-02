<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<Error v-if="error" :error="t('mail', 'Could not open mailbox')" message="" />
	<Loading v-else-if="loadingEnvelopes" :hint="t('mail', 'Loading messages')" />
	<Loading
		v-else-if="loadingCacheInitialization"
		:hint="t('mail', 'Loading messages')"
		:slow-hint="t('mail', 'Indexing your messages. This can take a bit longer for larger mailboxes.')"
	/>
	<EmptyMailbox v-else-if="envelopes.length === 0" key="empty" />
	<EnvelopeList
		v-else
		:account="account"
		:folder="folder"
		:envelopes="envelopes"
		:refreshing="refreshing"
		:loading-more="loadingMore"
		@delete="onDelete"
	/>
</template>

<script>
import EmptyMailbox from './EmptyMailbox'
import EnvelopeList from './EnvelopeList'
import Error from './Error'
import {findIndex, propEq} from 'ramda'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'
import Loading from './Loading'
import logger from '../logger'
import MailboxLockedError from '../errors/MailboxLockedError'
import MailboxNotCachedError from '../errors/MailboxNotCachedError'
import {matchError} from '../errors/match'
import {wait} from '../util/wait'

export default {
	name: 'Mailbox',
	components: {
		EmptyMailbox,
		EnvelopeList,
		Error,
		Loading,
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
		bus: {
			type: Object,
			required: true,
		},
		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},
	},
	data() {
		return {
			error: false,
			refreshing: false,
			loadingMore: false,
			loadingEnvelopes: true,
			loadingCacheInitialization: false,
		}
	},
	computed: {
		envelopes() {
			return this.$store.getters.getEnvelopes(this.account.id, this.folder.id, this.searchQuery)
		},
	},
	watch: {
		account() {
			this.loadEnvelopes()
		},
		folder() {
			this.loadEnvelopes()
		},
		searchQuery() {
			this.loadEnvelopes()
		},
	},
	created() {
		this.bus.$on('loadMore', this.loadMore)
		this.bus.$on('delete', this.onDelete)
		this.bus.$on('shortcut', this.handleShortcut)
	},
	async mounted() {
		return await this.loadEnvelopes()
	},
	destroyed() {
		this.bus.$off('loadMore', this.loadMore)
		this.bus.$off('delete', this.onDelete)
		this.bus.$off('shortcut', this.handleShortcut)
	},
	methods: {
		initializeCache() {
			this.loadingCacheInitialization = true
			this.error = false

			this.$store
				.dispatch('syncEnvelopes', {
					accountId: this.account.id,
					folderId: this.folder.id,
					query: this.searchQuery,
					init: true,
				})
				.then(() => {
					this.loadingCacheInitialization = false

					return this.loadEnvelopes()
				})
		},
		async loadEnvelopes() {
			logger.debug('fetching envelopes')
			this.loadingEnvelopes = true
			this.loadingCacheInitialization = false
			this.error = false

			try {
				const envelopes = await this.$store.dispatch('fetchEnvelopes', {
					accountId: this.account.id,
					folderId: this.folder.id,
					query: this.searchQuery,
				})

				logger.debug('envelopes fetched', {envelopes})

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
			} catch (error) {
				await matchError(error, {
					[MailboxLockedError.getName()]: async error => {
						logger.info('Mailbox is locked', {error})

						await wait(15 * 1000)
						// Keep trying
						await this.loadEnvelopes()
					},
					[MailboxNotCachedError.getName()]: async error => {
						logger.info('Mailbox not cached. Triggering initialization', {error})
						this.loadingEnvelopes = false

						try {
							await this.initializeCache()
						} catch (error) {
							logger.error('Could not initialize cache', {error})
							this.error = error
						}
					},
					default: error => {
						logger.error('Could not fetch envelopes', {error})
						this.loadingEnvelopes = false
						this.error = error
					},
				})
			}
		},
		async loadMore() {
			logger.debug('fetching next envelope page')
			this.loadingMore = true

			try {
				await this.$store.dispatch('fetchNextEnvelopePage', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					envelopes: this.envelopes,
					query: this.searchQuery,
				})
			} catch (error) {
				logger.error('could not fetch next envelope page', {error})
			} finally {
				this.loadingMore = false
			}
		},
		handleShortcut(e) {
			const envelopes = this.envelopes
			const currentUid = this.$route.params.messageUid

			if (!currentUid) {
				logger.debug('ignoring shortcut: no envelope selected')
				return
			}

			const current = envelopes.filter(e => e.uid === currentUid)
			if (current.length === 0) {
				logger.debug('ignoring shortcut: currently displayed messages is not in current envelope list')
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
						logger.debug('ignoring shortcut: head or tail of envelope list reached', {
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
					logger.debug('deleting', {env})
					this.onDelete({envelope: env})
					this.$store
						.dispatch('deleteMessage', {accountId: env.accountId, folderId: env.folderId, id: env.id})
						.catch(error => logger.error('could not delete envelope', {env, error}))

					break
				case 'flag':
					logger.debug('flagging envelope via shortkey', {env})
					this.$store
						.dispatch('toggleEnvelopeFlagged', env)
						.catch(error => logger.error('could not flag envelope via shortkey', {env, error}))
					break
				case 'refresh':
					logger.debug('syncing envelopes via shortkey')
					if (!this.refreshing) {
						this.sync()
					}

					break
				case 'unseen':
					logger.debug('marking message as seen/unseen via shortkey', {env})
					this.$store
						.dispatch('toggleEnvelopeSeen', env)
						.catch(error =>
							logger.error('could not mark envelope as seen/unseen via shortkey', {env, error})
						)
					break
				default:
					logger.warn('shortcut ' + e.srcKey + ' is unknown. ignoring.')
			}
		},
		async sync() {
			this.refreshing = true

			try {
				await this.$store.dispatch('syncEnvelopes', {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					query: this.searchQuery,
				})
			} catch (error) {
				matchError(error, {
					[MailboxLockedError.getName()](error) {
						logger.info('Background sync failed because the mailbox is locked', {error})
					},
					default(error) {
						logger.error('Could not sync envelopes: ' + error.message, {error})
					},
				})
			} finally {
				this.refreshing = false
			}
		},
		onDelete({envelope}) {
			const idx = findIndex(propEq('uid', envelope.uid), this.envelopes)
			if (idx === -1) {
				logger.debug('envelope to delete does not exist in envelope list')
				return
			}
			this.envelopes.splice(idx, 1)
			if (envelope.uid !== this.$route.params.messageUid) {
				logger.debug('other message open, not jumping to the next/previous message')
				return
			}

			const next = this.envelopes[idx === 0 ? idx : idx - 1]
			if (!next) {
				logger.debug('no next/previous envelope, not navigating')
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
	},
}
</script>

<style scoped></style>
