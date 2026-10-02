<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div
		class="mailbox"
		:class="{ 'empty-content': (!hasMessages && !loadingEnvelopes) || error }">
		<Error
			v-if="error"
			:error="t('mail', 'Could not open folder')"
			message=""
			role="alert" />
		<LoadingSkeleton v-else-if="loadingEnvelopes" :number-of-lines="20" />
		<Loading
			v-else-if="loadingCacheInitialization"
			:hint="t('mail', 'Loading messages …')"
			:slow-hint="t('mail', 'Indexing your messages. This can take a bit longer for larger folders.')" />
		<EmptyMailboxSection v-else-if="isPriorityInbox && !hasMessages" key="empty" />
		<EmptyMailbox v-else-if="!hasMessages" key="empty" />
		<template v-else-if="hasGroupedEnvelopes && !isPriorityInbox">
			<div v-for="[label, group] in groupEnvelopes" :key="label">
				<SectionTitle class="section-title" :name="getLabelForGroup(label)" />
				<EnvelopeList
					ref="envelopeLists"
					:account="account"
					:mailbox="mailbox"
					:cursor-id="cursorId"
					:search-query="searchQuery"
					:envelopes="group"
					:loading-more="false"
					:load-more-button="false"
					:skip-transition="skipListTransition"
					:date-grouped="true"
					@delete="onDelete" />
			</div>
		</template>
		<EnvelopeList
			v-else
			ref="envelopeLists"
			:account="account"
			:load-more-label="loadMoreLabel"
			:mailbox="mailbox"
			:cursor-id="cursorId"
			:search-query="searchQuery"
			:envelopes="envelopesToShow"
			:loading-more="loadingMore"
			:load-more-button="showLoadMore"
			:skip-transition="skipListTransition"
			@delete="onDelete"
			@load-more="loadMore" />
	</div>
</template>

<script>
import { showError, showWarning } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import { findIndex, propEq } from 'ramda'
import EmptyMailbox from './EmptyMailbox.vue'
import EmptyMailboxSection from './EmptyMailboxSection.vue'
import EnvelopeList from './EnvelopeList.vue'
import Error from './Error.vue'
import Loading from './Loading.vue'
import LoadingSkeleton from './LoadingSkeleton.vue'
import SectionTitle from './SectionTitle.vue'
import MailboxLockedError from '../errors/MailboxLockedError.js'
import MailboxNotCachedError from '../errors/MailboxNotCachedError.js'
import { matchError } from '../errors/match.js'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError.js'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { mailboxHasRights } from '../util/acl.js'
import { wait } from '../util/wait.js'

export default {
	name: 'Mailbox',
	components: {
		EmptyMailboxSection,
		EmptyMailbox,
		EnvelopeList,
		Error,
		Loading,
		LoadingSkeleton,
		SectionTitle,
	},

	props: {
		groupEnvelopes: {
			type: Array,
			required: false,
			default: () => [],
		},

		loadMoreLabel: {
			type: String,
			default: undefined,
		},

		account: {
			type: Object,
			required: true,
		},

		mailbox: {
			type: Object,
			required: true,
		},

		bus: {
			type: Object,
			required: true,
		},

		paginate: {
			type: String,
			default: 'scroll',
		},

		initialPageSize: {
			type: Number,
			default: 20,
		},

		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},

		isPriorityInbox: {
			type: Boolean,
			required: false,
			default: false,
		},
	},

	data() {
		return {
			error: false,
			refreshing: false,
			loadingMore: false,
			loadingEnvelopes: false,
			loadingCacheInitialization: false,
			loadMailboxInterval: undefined,
			expanded: false,
			cursorId: undefined,
			endReached: false,
			syncedMailboxes: new Set(),
			skipListTransition: false,
		}
	},

	computed: {
		...mapStores(useMainStore),
		sortOrder() {
			return this.mainStore.getPreference('sort-order', 'newest')
		},

		envelopes() {
			return this.mainStore.getEnvelopes(this.mailbox.databaseId, this.searchQuery)
		},

		envelopesToShow() {
			if (this.paginate === 'manual' && !this.expanded) {
				return this.envelopes.slice(0, this.initialPageSize)
			}
			return this.envelopes
		},

		hasGroupedEnvelopes() {
			return this.groupEnvelopes && this.groupEnvelopes.length > 0
		},

		hasMessages() {
			if (this.hasGroupedEnvelopes) {
				return this.groupEnvelopes.some(([, group]) => group.length > 0)
			}
			return this.envelopesToShow?.length > 0
		},

		showLoadMore() {
			return !this.endReached && this.paginate === 'manual'
		},
	},

	watch: {
		mailbox() {
			this.loadEnvelopes()
				.then(() => {
					logger.debug(`syncing mailbox ${this.mailbox.databaseId} (${this.query}) after folder change`)
					this.sync(false)
				})
		},

		searchQuery() {
			this.loadEnvelopes()
		},

		sortOrder() {
			this.loadEnvelopes()
		},
	},

	created() {
		this.bus.on('load-more', this.onScroll)
		this.bus.on('delete', this.onDelete)
		this.bus.on('archive', this.onArchive)
		this.bus.on('shortcut', this.handleShortcut)
		this.loadMailboxInterval = setInterval(this.loadMailbox, 60000)
	},

	async mounted() {
		if (this.mainStore.hasFetchedInitialEnvelopes) {
			return
		}

		await this.loadEnvelopes()
		logger.debug(`syncing folder ${this.mailbox.databaseId} (${this.searchQuery}) after mount`)
		await this.sync(false)

		await this.prefetchOtherMailboxes()

		this.mainStore.setHasFetchedInitialEnvelopesMutation(true)
	},

	destroyed() {
		this.bus.off('load-more', this.onScroll)
		this.bus.off('delete', this.onDelete)
		this.bus.off('archive', this.onArchive)
		this.bus.off('shortcut', this.handleShortcut)
		this.stopInterval()
	},

	methods: {
		initializeCache() {
			this.loadingCacheInitialization = true
			this.error = false

			logger.debug(`syncing folder ${this.mailbox.databaseId} (${this.query}) during cache initialization`)
			this.sync(true)
				.then(() => {
					this.loadingCacheInitialization = false

					return this.loadEnvelopes()
				})
		},

		async loadEnvelopes() {
			logger.debug(`Fetching envelopes for folder ${this.mailbox.databaseId} (${this.searchQuery})`, this.mailbox)
			this.endReached = false
			if (!this.syncedMailboxes.has(this.mailbox.databaseId + (this.searchQuery ?? ''))) {
				// Only trigger skeleton if we didn't sync envelopes yet
				this.loadingEnvelopes = true
			} else {
				this.skipListTransition = true
				this.$nextTick(() => {
					this.skipListTransition = false
				})
			}

			this.loadingCacheInitialization = false
			this.error = false

			try {
				const envelopes = await this.mainStore.fetchEnvelopes({
					mailboxId: this.mailbox.databaseId,
					query: this.searchQuery,
					limit: this.initialPageSize,
				})

				logger.debug(envelopes.length + ' envelopes fetched', { envelopes })

				this.syncedMailboxes.add(this.mailbox.databaseId + (this.searchQuery ?? ''))
				this.loadingEnvelopes = false
			} catch (error) {
				await matchError(error, {
					[MailboxLockedError.getName()]: async (error) => {
						logger.info(`Mailbox ${this.mailbox.databaseId} (${this.searchQuery}) is locked`, { error })
						await wait(15 * 1000)
						// Keep trying
						await this.loadEnvelopes()
					},
					[MailboxNotCachedError.getName()]: async (error) => {
						logger.info(`Mailbox ${this.mailbox.databaseId} (${this.searchQuery}) not cached. Triggering initialization`, { error })
						this.loadingEnvelopes = false

						try {
							await this.initializeCache()
						} catch (error) {
							logger.error(`Could not initialize cache of folder ${this.mailbox.databaseId} (${this.searchQuery})`, { error })
							this.error = error
						}
					},
					default: (error) => {
						logger.error(`Could not fetch envelopes of folder ${this.mailbox.databaseId} (${this.searchQuery})`, { error })
						this.loadingEnvelopes = false
						this.error = error
					},
				})
			}
		},

		async loadMore() {
			if (!this.hasMessages || this.loadingEnvelopes || this.loadingMore || this.endReached || this.error) {
				return
			}

			if (!this.expanded && this.envelopesToShow.length < this.envelopes.length) {
				logger.debug('expanding envelope list')
				this.expanded = true
				return
			}

			logger.debug('fetching next envelope page')
			this.loadingMore = true

			try {
				const envelopes = await this.mainStore.fetchNextEnvelopePage({
					mailboxId: this.mailbox.databaseId,
					query: this.searchQuery,
				})
				if (envelopes.length === 0) {
					logger.info('envelope list end reached')
					this.endReached = true
				}
			} catch (error) {
				logger.error('could not fetch next envelope page', { error })
			} finally {
				this.loadingMore = false
			}
		},

		async prefetchOtherMailboxes() {
			for (const mailbox of this.mainStore.getRecursiveMailboxIterator(this.account.id)) {
				if (mailbox.databaseId === this.mailbox.databaseId) {
					continue
				}

				if (!mailbox.isSubscribed) {
					continue
				}

				try {
					const envelopes = await this.mainStore.fetchEnvelopes({
						mailboxId: mailbox.databaseId,
						limit: this.initialPageSize,
						includeCacheBuster: true,
					})
					this.syncedMailboxes.add(mailbox.databaseId + (this.searchQuery ?? ''))
					logger.debug(`Prefetched ${envelopes.length} envelopes for folder ${mailbox.displayName} (${mailbox.databaseId})`)
				} catch (error) {
					if (error instanceof MailboxNotCachedError) {
						// Just ignore
						continue
					}

					logger.error(`Failed to prefetch envelopes for folder ${mailbox.displayName} (${mailbox.databaseId}): ${error}`, {
						error,
					})
				}
			}
		},

		hasDeleteAcl() {
			return mailboxHasRights(this.mailbox, 'x')
		},

		hasSeenAcl() {
			return mailboxHasRights(this.mailbox, 's')
		},

		hasArchiveAcl() {
			return mailboxHasRights(this.mailbox, 'te')
		},

		/**
		 * Move the list cursor without opening anything.
		 *
		 * @param {number} delta 1 for next, -1 for previous
		 */
		moveCursor(delta) {
			const envelopes = this.envelopes
			if (envelopes.length === 0) {
				return
			}

			const current = envelopes.findIndex((env) => env.databaseId === this.cursorId)
			const next = current === -1
				? (delta > 0 ? 0 : envelopes.length - 1)
				: Math.min(envelopes.length - 1, Math.max(0, current + delta))

			this.cursorId = envelopes[next].databaseId
			this.$nextTick(() => {
				const el = this.$el.querySelector('.envelope--cursored')
				if (el) {
					el.scrollIntoView({ block: 'nearest' })
				}
			})
		},

		/**
		 * The mounted envelope lists.
		 *
		 * Date grouped views mount one per group, so this is normalised to an
		 * array. Refs inside v-for are not reactive, so this may only be read
		 * while handling an event, never from a computed.
		 *
		 * @return {Array<object>} envelope list components
		 */
		envelopeLists() {
			return [].concat(this.$refs.envelopeLists ?? [])
		},

		/**
		 * @return {Array<object>} the lists that currently hold a selection
		 */
		listsWithSelection() {
			return this.envelopeLists().filter((list) => list.selection.length > 0)
		},

		/** Open the message the cursor is on. */
		openCursor() {
			const target = this.envelopes.find((env) => env.databaseId === this.cursorId)
			if (!target) {
				return
			}

			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
					threadId: target.databaseId,
				},
			})
		},

		/** Close the open message, leaving the cursor on it. */
		backToList() {
			const currentId = parseInt(this.$route.params.threadId, 10)
			if (!currentId) {
				return
			}

			this.cursorId = currentId
			this.$router.push({
				name: 'mailbox',
				params: {
					mailboxId: this.$route.params.mailboxId,
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
			})
		},

		/** Focus the message search field. */
		focusSearch() {
			// SearchMessages renders a type="text" input, not type="search".
			const input = document.querySelector('.search-messages--input')
			if (input) {
				input.focus()
				input.select?.()
			}
		},

		/**
		 * Move one envelope to its account's archive folder.
		 *
		 * @param {object} env envelope to archive
		 * @return {Promise<boolean>} false when the account cannot archive at all,
		 *   so that a bulk run stops instead of repeating the same warning
		 */
		async archiveEnvelope(env) {
			// In unified mailboxes this.account is the unified account which
			// has no archive mailbox, so resolve the envelope's actual account
			const account = this.mainStore.getAccount(env.accountId)

			if (account.archiveMailboxId === null) {
				showWarning(t('mail', 'To archive a message please configure an archive folder in account settings'))
				return false
			}

			if (!this.hasArchiveAcl()) {
				showWarning(t('mail', 'You are not allowed to move this message to the archive folder and/or delete this message from the current folder'))
				return false
			}

			if (env.mailboxId === account.archiveMailboxId) {
				logger.debug('message is already in archive folder')
				return true
			}

			logger.debug('archiving', { env })
			this.onDelete(env.databaseId)
			try {
				await this.mainStore.moveThread({
					envelope: env,
					destMailboxId: account.archiveMailboxId,
				})
			} catch (error) {
				logger.error('could not archive envelope', {
					env,
					error,
				})

				showError(t('mail', 'Could not archive message'))
			}

			return true
		},

		/**
		 * Archive every marked message.
		 *
		 * The multiselect header has no archive button, so unlike the other bulk
		 * actions this one cannot delegate to the list.
		 *
		 * @param {Array<object>} lists envelope lists holding a selection
		 */
		async archiveSelected(lists) {
			for (const list of lists) {
				const envelopes = [...list.selectedEnvelopes]
				list.unselectAll()

				for (const envelope of envelopes) {
					if (!await this.archiveEnvelope(envelope)) {
						return
					}
				}
			}
		},

		async handleShortcut(e) {
			const envelopes = this.envelopes
			const currentId = parseInt(this.$route.params.threadId, 10)

			const env = envelopes.find((e) => e.databaseId === currentId)
			const idx = envelopes.indexOf(env)
			let next

			// x marks the message under the cursor. Marking is a list gesture, so
			// it only applies where the cursor lives.
			if (e.srcKey === 'select') {
				if (this.cursorId === undefined) {
					return
				}

				this.envelopeLists().some((list) => list.toggleSelectionById(this.cursorId))
				return
			}

			// While anything is marked the acting verbs work on the marked set.
			// They must never fall through to the cursor or the open message:
			// acting on a message the user did not mark is the one failure worth
			// guarding against here, so an unsupported verb does nothing at all.
			const selectedLists = this.listsWithSelection()
			if (selectedLists.length > 0) {
				if (e.srcKey === 'back') {
					selectedLists.forEach((list) => list.unselectAll())
					return
				}

				if (e.srcKey === 'arch') {
					return this.archiveSelected(selectedLists)
				}

				if (['del', 'delAlt', 'delBackspace', 'flag', 'unseen', 'junk', 'important'].includes(e.srcKey)) {
					selectedLists.forEach((list) => list.handleBulkShortcut(e.srcKey))
					return
				}
			}

			// Verbs that work without an open message.
			if (e.srcKey === 'search') {
				return this.focusSearch()
			}
			if (e.srcKey === 'back') {
				return this.backToList()
			}
			if (e.srcKey === 'help' || e.srcKey === 'helpAlt') {
				return window.dispatchEvent(new CustomEvent('mail:show-settings'))
			}
			if (e.srcKey === 'compose') {
				return this.mainStore.startComposerSession({
					data: { accountId: this.account.accountId },
				})
			}

			// Junk and important act on the open message or the cursor.
			if (e.srcKey === 'junk' || e.srcKey === 'important') {
				const target = env ?? envelopes.find((envelope) => envelope.databaseId === this.cursorId)
				if (!target) {
					return
				}

				if (e.srcKey === 'important') {
					return this.mainStore.toggleEnvelopeImportant(target)
				}

				await this.mainStore.moveEnvelopeToJunk(target)
				return this.onDelete(target.databaseId)
			}

			// Reply/forward act on the open message, or on the cursor when
			// browsing the list.
			if (['reply', 'replyAll', 'forward'].includes(e.srcKey)) {
				const target = env ?? envelopes.find((envelope) => envelope.databaseId === this.cursorId)
				if (!target) {
					return
				}

				return this.mainStore.startComposerSession({
					reply: {
						mode: e.srcKey === 'forward' ? 'forward' : e.srcKey,
						data: target,
					},
				})
			}

			// Gmail's model: in the list a cursor moves without opening, and
			// only 'open' follows it. Inside a message j/k walk the thread
			// list as before.
			if (!env && envelopes.length > 0) {
				if (e.srcKey === 'next' || e.srcKey === 'prev') {
					return this.moveCursor(e.srcKey === 'next' ? 1 : -1)
				}
				if (e.srcKey === 'open' || e.srcKey === 'openAlt') {
					return this.openCursor()
				}
			}

			if (e.srcKey !== 'refresh' && !env) {
				logger.debug('envelope is not in the list, ignoring shortcut', {
					srcKey: e.srcKey,
				})
				return
			}

			switch (e.srcKey) {
				case 'next':
				case 'prev':
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

					// Keep the selected account-mailbox combination, but navigate to a different message
					// (it's not a bug that we don't use next.accountId and next.mailboxId here)
					this.$router.push({
						name: 'message',
						params: {
							mailboxId: this.$route.params.mailboxId,
							filter: this.$route.params.filter ? this.$route.params.filter : undefined,
							threadId: next.databaseId,
						},
					})
					break
				case 'delAlt':
				case 'delBackspace':
				case 'del':
					if (!this.hasDeleteAcl()) {
						return
					}
					logger.debug('deleting', { env })
					this.onDelete(env.databaseId)
					try {
						await this.mainStore.deleteThread({
							envelope: env,
						})
					} catch (error) {
						logger.error('could not delete envelope', {
							env,
							error,
						})

						showError(await matchError(error, {
							[NoTrashMailboxConfiguredError.getName()]() {
								return t('mail', 'No trash folder configured')
							},
							default() {
								return t('mail', 'Could not delete message')
							},
						}))
					}

					break
				case 'arch':
					logger.debug('archiving via shortcut')
					await this.archiveEnvelope(env)
					break
				case 'flag':
					logger.debug('flagging envelope via shortkey', { env })
					this.mainStore.toggleEnvelopeFlagged(env).catch((error) => logger.error('could not flag envelope via shortkey', {
						env,
						error,
					}))
					break
				case 'refresh':
					logger.debug(`syncing folder ${this.mailbox.databaseId} (${this.searchQuery}) per shortcut`)
					this.sync(false)

					break
				case 'unseen':
					logger.debug('marking as seen/unseen via shortcut')

					if (!this.hasSeenAcl()) {
						showWarning(t('mail', 'Your IMAP server does not support storing the seen/unseen state.'))
						return
					}

					logger.debug('marking as seen/unseen', { env })
					try {
						await this.mainStore.toggleEnvelopeSeen({ envelope: env })
					} catch (error) {
						logger.error('could not mark envelope as seen/unseen via shortkey', {
							env,
							error,
						})
						showError(t('mail', 'Could not mark message as seen/unseen'))
					}
					break
				default:
					logger.warn('shortcut ' + e.srcKey + ' is unknown. ignoring.')
			}
		},

		async sync(init = false) {
			if (this.refreshing) {
				logger.debug(`already sync'ing folder ${this.mailbox.databaseId} (${this.searchQuery}), aborting`, { init })
				return
			}

			this.refreshing = true
			try {
				await this.mainStore.syncEnvelopes({
					mailboxId: this.mailbox.databaseId,
					query: this.searchQuery,
					init,
				})
			} catch (error) {
				matchError(error, {
					[MailboxLockedError.getName()](error) {
						logger.info('Background sync failed because the folder is locked', {
							error,
							init,
						})
					},
					default(error) {
						logger.error('Could not sync envelopes: ' + error.message, {
							error,
							init,
						})
					},
				})
				throw error
			} finally {
				this.refreshing = false
				logger.debug(`finished sync'ing folder ${this.mailbox.databaseId} (${this.searchQuery})`, { init })

				this.mainStore.updateSyncTimestamp()
			}
		},

		// onDelete(id): Load more message and navigate to other message if needed
		// id: The id of the message being delete
		onDelete(id) {
			// Get a new message
			this.mainStore.fetchNextEnvelopes({
				mailboxId: this.mailbox.databaseId,
				query: this.searchQuery,
				quantity: 1,
			})
			const idx = findIndex(propEq(id, 'databaseId'), this.envelopes)
			if (idx === -1) {
				logger.debug('envelope to delete does not exist in envelope list')
				return
			}
			if (id !== this.$route.params.threadId) {
				logger.debug('other message open, not jumping to the next/previous message')
				return
			}

			const next = this.envelopes[idx + 1] ?? this.envelopes[idx - 1]
			if (!next) {
				logger.debug('no next/previous envelope, not navigating')
				return
			}

			// Keep the selected mailbox, but navigate to a different message
			// (it's not a bug that we don't use next.mailboxId here)
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
					threadId: next.databaseId,
				},
			})
		},

		onScroll() {
			if (this.paginate !== 'scroll') {
				logger.debug('ignoring scroll pagination')
				return
			}

			this.loadMore()
		},

		async loadMailbox() {
			// When the account is unified or inbox, return nothing, else sync the mailbox
			if (this.account.isUnified || this.mailbox.specialRole === 'inbox') {
				return
			}
			try {
				logger.debug(`syncing folder ${this.mailbox.databaseId} (${this.searchQuery}) in background`)
				await this.sync(false)
			} catch (error) {
				logger.error('Background sync failed: ' + error.message, { error })
			}
		},

		stopInterval() {
			clearInterval(this.loadMailboxInterval)
			this.loadMailboxInterval = undefined
		},

		getLabelForGroup(group) {
			switch (group) {
				case 'lastHour':
					return t('mail', 'Last hour')
				case 'today':
					return t('mail', 'Today')
				case 'yesterday':
					return t('mail', 'Yesterday')
				case 'lastWeek':
					return t('mail', 'Last week')
				case 'lastMonth':
					return t('mail', 'Last month')
				default:
					return group
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.mailbox {
	height: 100%;
}

// Fix vertical space between sections in priority inbox
.nameimportant {
	:deep(#load-more-mail-messages) {
		margin-top: 0;
	}
}

.empty-content {
	height: 100%;
	display: flex;
	justify-content: center;
}
</style>
