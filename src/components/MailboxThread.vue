<template>
	<AppContent pane-config-key="mail" :show-details="isThreadShown" @update:showDetails="hideMessage">
		<div slot="list"
			:class="{ header__button: !showThread || !isMobile }">
			<SearchMessages v-if="!showThread || !isMobile"
				:mailbox="mailbox"
				@search-changed="onUpdateSearchQuery" />
			<AppContentList
				v-infinite-scroll="onScroll"
				v-shortkey.once="shortkeys"
				class="envelope-list"
				infinite-scroll-immediate-check="false"
				:show-details="showThread"
				:infinite-scroll-disabled="false"
				:infinite-scroll-distance="10"
				role="heading"
				:aria-level="2"
				@shortkey.native="onShortcut">
				<Mailbox
					v-if="!mailbox.isPriorityInbox"
					:account="account"
					:mailbox="mailbox"
					:search-query="query"
					:bus="bus"
					:open-first="mailbox.specialRole !== 'drafts'" />
				<template v-else>
					<div v-show="hasImportantEnvelopes" class="app-content-list-item">
						<SectionTitle class="important" :name="t('mail', 'Important')" />
						<Popover trigger="hover focus">
							<ButtonVue slot="trigger"
								type="tertiary-no-background"
								:aria-label="t('mail', 'Important info')"
								class="button">
								<template #icon>
									<IconInfo :size="20" />
								</template>
							</ButtonVue>
							<p class="important-info">
								{{ importantInfo }}
							</p>
						</Popover>
					</div>
					<Mailbox v-show="hasImportantEnvelopes"
						class="nameimportant"
						:account="unifiedAccount"
						:mailbox="unifiedInbox"
						:search-query="appendToSearch(priorityImportantQuery)"
						:paginate="'manual'"
						:is-priority-inbox="true"
						:initial-page-size="importantMessagesInitialPageSize"
						:collapsible="true"
						:bus="bus" />
					<SectionTitle v-show="hasImportantEnvelopes"
						class="app-content-list-item other"
						:name="t('mail', 'Other')" />
					<Mailbox
						class="nameother"
						:account="unifiedAccount"
						:mailbox="unifiedInbox"
						:open-first="false"
						:search-query="appendToSearch(priorityOtherQuery)"
						:is-priority-inbox="true"
						:bus="bus" />
				</template>
			</AppContentList>
		</div>
		<Thread v-if="showThread" @delete="deleteMessage" />
		<NoMessageSelected v-else-if="hasEnvelopes && !isMobile" />
	</AppContent>
</template>

<script>
import { NcAppContent as AppContent, NcAppContentList as AppContentList, NcButton as ButtonVue, NcPopover as Popover } from '@nextcloud/vue'

import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'
import SectionTitle from './SectionTitle'
import Vue from 'vue'

import infiniteScroll from '../directives/infinite-scroll'
import IconInfo from 'vue-material-design-icons/Information'
import logger from '../logger'
import Mailbox from './Mailbox'
import SearchMessages from './SearchMessages'
import NoMessageSelected from './NoMessageSelected'
import Thread from './Thread'
import { UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID } from '../store/constants'
import {
	priorityImportantQuery,
	priorityOtherQuery,
} from '../util/priorityInbox'
import { detect, html } from '../util/text'

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
			bus: new Vue(),
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
		}
	},
	computed: {
		unifiedAccount() {
			return this.$store.getters.getAccount(UNIFIED_ACCOUNT_ID)
		},
		unifiedInbox() {
			return this.$store.getters.getMailbox(UNIFIED_INBOX_ID)
		},
		hasEnvelopes() {
			return this.$store.getters.getEnvelopes(this.mailbox.databaseId, this.searchQuery).length > 0
		},
		hasImportantEnvelopes() {
			return this.$store.getters.getEnvelopes(this.unifiedInbox.databaseId, this.appendToSearch(priorityImportantQuery)).length > 0
		},
		importantMessagesInitialPageSize() {
			if (window.innerHeight > 900) {
				return 7
			}
			if (window.innerHeight > 750) {
				return 5
			}
			return 3
		},
		showThread() {
			return (this.mailbox.isPriorityInbox === true || this.hasEnvelopes) && this.$route.name === 'message'
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
		isThreadShown() {
			return !!this.$route.params.threadId
		},
	},
	watch: {
		$route() {
			this.handleMailto()
		},
		mailbox() {
			clearTimeout(this.startMailboxTimer)
			setTimeout(this.saveStartMailbox, START_MAILBOX_DEBOUNCE)
		},
	},
	created() {
		this.handleMailto()
	},
	mounted() {
		setTimeout(this.saveStartMailbox, START_MAILBOX_DEBOUNCE)
	},
	beforeUnmount() {
		clearTimeout(this.startMailboxTimer)
	},
	methods: {
		deleteMessage(id) {
			this.bus.$emit('delete', id)
		},
		onScroll(event) {
			logger.debug('scroll', { event })

			this.bus.$emit('load-more')
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
				this.$store.dispatch('showMessageComposer', {
					data: {
						accountId,
						to: this.stringToRecipients(this.$route.query.to),
						cc: this.stringToRecipients(this.$route.query.cc),
						subject: this.$route.query.subject || '',
						body: this.$route.query.body ? detect(this.$route.query.body) : html(''),
					},
				})
			}
		},
		async saveStartMailbox() {
			const currentStartMailboxId = this.$store.getters.getPreference('start-mailbox-id')
			if (currentStartMailboxId === this.mailbox.databaseId) {
				return
			}
			logger.debug(`Saving mailbox ${this.mailbox.databaseId} as start mailbox`)

			try {
				await this.$store
					.dispatch('savePreference', {
						key: 'start-mailbox-id',
						value: this.mailbox.databaseId,
					})
			} catch (error) {
				// Catch and log. This is not critical.
				logger.warn('Could not update start mailbox id', {
					error,
				})
			}
		},
		stringToRecipients(str) {
			if (str === undefined) {
				return []
			}

			return [
				{
					label: str,
					email: str,
				},
			]
		},
		onUpdateSearchQuery(query) {
			this.searchQuery = query
		},
	},
}
</script>

<style lang="scss" scoped>
.v-popover > .trigger > * {
	z-index: 1;
}

.important-info {
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
	right: 2px;

	&:hover,
	&:focus {
		background-color: var(--color-background-dark);
	}
}
:deep(.button-vue--vue-secondary) {
	position: sticky;
	top:40px;
	left: 10px;
}
:deep(.app-content-wrapper) {
	overflow: auto;
}
.envelope-list {
	overflow-y: auto;
	padding: 0 4px;
}
.information-icon {
	opacity: .7;
}
.header__button {
	display: flex;
	flex: 1 0 0;
	flex-direction: column;
	height: calc(100vh - var(--header-height));
}
</style>
