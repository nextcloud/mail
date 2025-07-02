<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<AppContent pane-config-key="mail"
		:layout="layoutMode"
		:show-details="isThreadShown"
		:list-min-width="horizontalListMinWidth"
		:list-max-width="horizontalListMaxWidth"
		@update:showDetails="hideMessage">
		<template #list>
			<div :class="{ list__wrapper: !showThread || !isMobile }">
				<div v-if="!showThread || !isMobile" class="sticky-header">
					<SearchMessages :mailbox="mailbox"
						:account-id="account.accountId"
						@search-changed="onUpdateSearchQuery" />
				</div>
				<AppContentList v-infinite-scroll="onScroll"
					v-shortkey.once="shortkeys"
					class="envelope-list"
					infinite-scroll-immediate-check="false"
					:show-details="showThread"
					:infinite-scroll-disabled="false"
					:infinite-scroll-distance="300"
					role="heading"
					:aria-level="2"
					@shortkey.native="onShortcut">
					<Mailbox v-if="!mailbox.isPriorityInbox"
						:account="account"
						:mailbox="mailbox"
						:search-query="query"
						:bus="bus"
						:open-first="mailbox.specialRole !== 'drafts'"
						:group-envelopes="groupEnvelopes"
						:initial-page-size="messagesOrderBydate"
						:collapsible="true" />
					<template v-else>
						<div v-show="hasFollowUpEnvelopes"
							class="app-content-list-item">
							<SectionTitle class="section-title"
								:name="t('mail', 'Follow up')" />
							<Popover trigger="hover focus">
								<template #trigger>
									<ButtonVue type="tertiary-no-background"
										:aria-label="t('mail', 'Follow up info')"
										class="button">
										<template #icon>
											<IconInfo :size="20" />
										</template>
									</ButtonVue>
								</template>
								<p class="section-header-info">
									{{ followupInfo }}
								</p>
							</Popover>
						</div>
						<Mailbox v-show="hasFollowUpEnvelopes"
							:load-more-label="t('mail', 'Load more follow ups')"
							:account="unifiedAccount"
							:mailbox="followUpMailbox"
							:search-query="appendToSearch(followUpQuery)"
							:paginate="'manual'"
							:is-priority-inbox="true"
							:initial-page-size="followUpMessagesInitialPageSize"
							:collapsible="true"
							:bus="bus" />
						<div v-show="hasImportantEnvelopes" class="app-content-list-item">
							<SectionTitle class="section-title important"
								:name="t('mail', 'Important')" />
							<Popover trigger="hover focus">
								<template #trigger>
									<ButtonVue type="tertiary-no-background"
										:aria-label="t('mail', 'Important info')"
										class="button">
										<template #icon>
											<IconInfo :size="20" />
										</template>
									</ButtonVue>
								</template>
								<p class="section-header-info">
									{{ importantInfo }}
								</p>
							</Popover>
						</div>
						<Mailbox v-show="hasImportantEnvelopes"
							class="nameimportant"
							:load-more-label="t('mail', 'Load more important messages')"
							:account="unifiedAccount"
							:mailbox="unifiedInbox"
							:search-query="appendToSearch(priorityImportantQuery)"
							:paginate="'manual'"
							:is-priority-inbox="true"
							:initial-page-size="importantMessagesInitialPageSize"
							:collapsible="true"
							:bus="bus" />
						<SectionTitle v-show="hasImportantEnvelopes"
							class="app-content-list-item section-title other"
							:name="t('mail', 'Other')" />
						<Mailbox class="nameother"
							:load-more-label="t('mail', 'Load more other messages')"
							:account="unifiedAccount"
							:mailbox="unifiedInbox"
							:search-query="appendToSearch(priorityOtherQuery)"
							:is-priority-inbox="true"
							:bus="bus" />
					</template>
				</AppContentList>
			</div>
		</template>

		<Thread v-if="showThread" :current-account-email="account.emailAddress" @delete="deleteMessage" />
		<NoMessageSelected v-else-if="hasEnvelopes" />
	</AppContent>
</template>

<script>
import { NcAppContent as AppContent, NcAppContentList as AppContentList, NcButton as ButtonVue, NcPopover as Popover } from '@nextcloud/vue'

import isMobile from '@nextcloud/vue/dist/Mixins/isMobile.js'
import SectionTitle from './SectionTitle.vue'
import mitt from 'mitt'
import addressParser from 'address-rfc2822'

import infiniteScroll from '../directives/infinite-scroll.js'
import IconInfo from 'vue-material-design-icons/InformationOutline.vue'
import logger from '../logger.js'
import Mailbox from './Mailbox.vue'
import SearchMessages from './SearchMessages.vue'
import NoMessageSelected from './NoMessageSelected.vue'
import Thread from './Thread.vue'
import {
	FOLLOW_UP_MAILBOX_ID,
	PRIORITY_INBOX_ID,
	UNIFIED_ACCOUNT_ID,
	UNIFIED_INBOX_ID,
} from '../store/constants.js'
import {
	priorityImportantQuery,
	priorityOtherQuery,
} from '../util/priorityInbox.js'
import { detect, html } from '../util/text.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

const START_MAILBOX_DEBOUNCE = 5 * 1000

export default {
	name: 'MailboxThread',
	directives: {
		infiniteScroll,
	},
	components: {
		AppContent,
		AppContentList,
		ButtonVue,
		IconInfo,
		Mailbox,
		NoMessageSelected,
		Popover,
		SectionTitle,
		SearchMessages,
		Thread,
	},
	mixins: [isMobile],
	props: {
		account: {
			type: Object,
			required: true,
		},
		mailbox: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			// eslint-disable-next-line
			importantInfo: t('mail', 'Messages will automatically be marked as important based on which messages you interacted with or marked as important. In the beginning you might have to manually change the importance to teach the system, but it will improve over time.'),
			followupInfo: t('mail', 'Messages sent by you that require a reply but did not receive one after a couple of days will be shown here.'),
			bus: mitt(),
			searchQuery: undefined,
			shortkeys: {
				del: ['del'],
				arch: ['a'],
				flag: ['s'],
				next: ['arrowright'],
				prev: ['arrowleft'],
				refresh: ['r'],
				unseen: ['u'],
			},
			priorityImportantQuery,
			priorityOtherQuery,
			startMailboxTimer: undefined,
			hasContent: false,
		}
	},
	computed: {
		...mapStores(useMainStore),

		layoutMode() {
			return this.mainStore.getPreference('layout-mode', 'vertical-split')
		},
		horizontalListMinWidth() {
			return this.layoutMode === 'horizontal-split' ? 40 : 30
		},
		horizontalListMaxWidth() {
			return this.layoutMode === 'horizontal-split' ? 60 : 50
		},
		unifiedAccount() {
			return this.mainStore.getAccount(UNIFIED_ACCOUNT_ID)
		},
		unifiedInbox() {
			return this.mainStore.getMailbox(UNIFIED_INBOX_ID)
		},
		followUpMailbox() {
			return this.mainStore.getMailbox(FOLLOW_UP_MAILBOX_ID)
		},
		/**
		 * @return {string|undefined}
		 */
		followUpQuery() {
			const tag = this.mainStore.getFollowUpTag
			if (!tag) {
				logger.warn('No follow-up tag available')
				return undefined
			}

			const notAfter = new Date()
			notAfter.setDate(notAfter.getDate() - 4)
			const dateToTimestamp = (date) => Math.round(date.getTime() / 1000)
			return `tags:${tag.id} end:${dateToTimestamp(notAfter)}`
		},
		hasEnvelopes() {
			if (this.mailbox.isPriorityInbox) {
				return this.mainStore.getEnvelopes(this.mailbox.databaseId, this.appendToSearch(priorityImportantQuery)).length > 0
					|| this.mainStore.getEnvelopes(this.mailbox.databaseId, this.appendToSearch(priorityOtherQuery)).length > 0
			}
			return this.mainStore.getEnvelopes(this.mailbox.databaseId, this.searchQuery).length > 0
		},
		hasImportantEnvelopes() {
			const map = this.mainStore.getEnvelopes(
				this.unifiedInbox.databaseId,
				this.appendToSearch(this.priorityImportantQuery),
			)
			const envelopes = Array.isArray(map) ? map : Array.from(map?.values() || [])
			return envelopes.length > 0
		},
		/**
		 * @return {boolean}
		 */
		hasFollowUpEnvelopes() {
			if (!this.followUpQuery) {
				return false
			}

			const map = this.mainStore.getEnvelopes(FOLLOW_UP_MAILBOX_ID, this.followUpQuery)
			const envelopes = Array.isArray(map) ? map : Array.from(map?.values() || [])
			return envelopes.length > 0
		},
		importantMessagesInitialPageSize() {
			if (window.innerHeight > 1024) {
				return 7
			}
			if (window.innerHeight > 750) {
				return 5
			}
			return 3
		},
		/**
		 * @return {number}
		 */
		messagesOrderBydate() {
			return 10
		},
		/**
		 * @return {number}
		 */
		followUpMessagesInitialPageSize() {
			return 5
		},
		showThread() {
			return this.$route.name === 'message'
				&& this.$route.params.threadId !== 'mailto'
		},
		query() {
			if (this.$route.params.filter === 'starred') {
				if (this.searchQuery) {
					return this.appendToSearch('is:starred')
				}
				return 'is:starred'
			}
			return this.searchQuery
		},
		isThreadShown() {
			return !!this.$route.params.threadId
		},
		groupEnvelopes() {
			const allEnvelopes = this.mainStore.getEnvelopes(this.mailbox.databaseId, this.searchQuery)
			return this.groupEnvelopesByDate(allEnvelopes, this.mainStore.syncTimestamp)
		},
	},
	watch: {
		async $route(to) {
			this.handleMailto()
			if (to.name === 'mailbox' && to.params.mailboxId === PRIORITY_INBOX_ID) {
				await this.onPriorityMailboxOpened()
			} else if (this.isThreadShown) {
				await this.fetchEnvelopes()
			}
		},
		async hasFollowUpEnvelopes(value) {
			if (!value) {
				return
			}

			await this.onPriorityMailboxOpened()
		},
		mailbox() {
			clearTimeout(this.startMailboxTimer)
			setTimeout(this.saveStartMailbox, START_MAILBOX_DEBOUNCE)
			this.fetchEnvelopes()
		},
	},
	created() {
		this.handleMailto()
	},
	async mounted() {
		setTimeout(this.saveStartMailbox, START_MAILBOX_DEBOUNCE)
		if (this.isThreadShown) {
			await this.fetchEnvelopes()
		}
	},
	beforeUnmount() {
		clearTimeout(this.startMailboxTimer)
	},
	methods: {
		groupEnvelopesByDate(envelopes, syncTimestamp) {
			const now = new Date(syncTimestamp)
			const oneHourAgo = new Date(now.getTime() - 60 * 60 * 1000)
			const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
			const startOfYesterday = new Date(startOfToday)
			startOfYesterday.setDate(startOfYesterday.getDate() - 1)
			const startOfLastWeek = new Date(now)
			startOfLastWeek.setDate(startOfLastWeek.getDate() - 7)
			const startOfLastMonth = new Date(now)
			startOfLastMonth.setMonth(startOfLastMonth.getMonth() - 1)

			const groups = {
				lastHour: [],
				today: [],
				yesterday: [],
				lastWeek: [],
				lastMonth: [],
				older: [],
			}

			for (const envelope of envelopes) {
				const date = new Date(envelope.dateInt * 1000)
				if (date >= oneHourAgo) {
					groups.lastHour.push(envelope)
				} else if (date >= startOfToday) {
					groups.today.push(envelope)
				} else if (date >= startOfYesterday && date < startOfToday) {
					groups.yesterday.push(envelope)
				} else if (date >= startOfLastWeek) {
					groups.lastWeek.push(envelope)
				} else if (date >= startOfLastMonth) {
					groups.lastMonth.push(envelope)
				} else {
					groups.older.push(envelope)
				}
			}

			return Object.fromEntries(
				Object.entries(groups).filter(([_, list]) => list.length > 0),
			)
		},
		async fetchEnvelopes() {
			const existingEnvelopes = this.mainStore.getEnvelopes(this.mailbox.databaseId, this.searchQuery || '')
			if (!existingEnvelopes.length) {
				await this.mainStore.fetchEnvelopes({
					mailboxId: this.mailbox.databaseId,
					query: this.searchQuery || '',
				})
			}
		},
		async onPriorityMailboxOpened() {
			logger.debug('Priority inbox was opened')

			await this.mainStore.checkFollowUpReminders({ query: this.followUpQuery })
		},
		deleteMessage(id) {
			this.bus.emit('delete', id)
		},
		onScroll(event) {
			logger.debug('scroll', { event })

			this.bus.emit('load-more')
		},
		onShortcut(e) {
			this.bus.emit('shortcut', e)
		},
		appendToSearch(str) {
			if (this.searchQuery === undefined) {
				return str
			}
			return this.searchQuery + ' ' + str
		},
		hideMessage() {
			this.$router.replace({
				name: 'mailbox',
				params: {
					mailboxId: this.$route.params.mailboxId,
					filter: this.$route.params?.filter,
				},
			})
		},
		handleMailto() {
			if (this.$route.name === 'message' && this.$route.params.threadId === 'mailto') {
				let accountId
				// Only preselect an account when we're not in a unified mailbox
				if (this.$route.params.accountId !== 0 && this.$route.params.accountId !== '0') {
					accountId = parseInt(this.$route.params.accountId, 10)
				}
				this.mainStore.startComposerSession({
					data: {
						accountId,
						to: this.stringToRecipients(this.$route.query.to),
						cc: this.stringToRecipients(this.$route.query.cc),
						bcc: this.stringToRecipients(this.$route.query.bcc),
						subject: this.$route.query.subject || '',
						body: this.$route.query.body ? detect(this.$route.query.body) : html(''),
					},
				})
			}
		},
		async saveStartMailbox() {
			const currentStartMailboxId = this.mainStore.getPreference('start-mailbox-id')
			if (currentStartMailboxId === this.mailbox.databaseId) {
				return
			}
			logger.debug(`Saving folder ${this.mailbox.databaseId} as start folder`)

			try {
				await this.mainStore.savePreference({
					key: 'start-mailbox-id',
					value: this.mailbox.databaseId,
				})
			} catch (error) {
				// Catch and log. This is not critical.
				logger.warn('Could not update start folder id', {
					error,
				})
			}
		},
		stringToRecipients(str) {
			if (str === undefined) {
				return []
			}

			let addresses = []
			try {
				addresses = addressParser.parse(str)
			} catch (error) {
				logger.debug('could not parse string into email addresses', { str, error })
			}

			return addresses.map(address => {
				const result = {
					label: address.name(),
					email: address.address,
				}

				if (result.label === '') {
					result.label = result.email
				}

				return result
			})
		},
		onUpdateSearchQuery(query) {
			this.searchQuery = query
		},
	},
}
</script>

<style lang="scss" scoped>
.section-title {
	:deep(h2) {
		margin: 0 !important;
	}
}

:deep(.app-content-list) {
	flex: 1 1 auto;
	height: 100% !important;
	min-height: 0;
	position: absolute;
	overflow: scroll;
	width: 100% !important;
	top: 52px;
}

:deep(.app-content-wrapper) {
	display: flex;
	flex-direction: column;
	height: 100%;
	overflow: hidden;
}

.v-popover > .trigger > * {
	z-index: 1;
}

.section-header-info {
	max-width: 230px;
	padding: 16px;
}

.app-content-list {
	// Required for centering the loading indicator
	display: flex;
}

.app-content-list-item:hover {
	background: transparent;
}

.app-content-list-item {
	flex: 0;
}

.button {
	background-color: var(--color-main-background);
	margin-bottom: 3px;
	inset-inline-end: 2px;

	&:hover,
	&:focus {
		background-color: var(--color-background-dark);
	}
}

.envelope-list {
	flex: 1 1 auto;
	overflow-y: auto;
	min-height: 0;
}

.information-icon {
	opacity: .7;
}
@media only screen and (max-width: 1024px) {
	.information-icon {
		margin-bottom: 20px;
	}
}

.list__wrapper {
	display: flex;
	flex: 1 1 auto;
	flex-direction: column;
	height: 100%;
	overflow: hidden;
}

:deep(.app-details-toggle) {
	opacity: 1;
}

:deep(.app-content-wrapper.app-content-wrapper--no-split.app-content-wrapper--show-details) {
	overflow-y: scroll !important;
}
</style>
