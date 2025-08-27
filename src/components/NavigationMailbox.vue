<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppNavigationItem v-if="visible"
		:id="genId(mailbox)"
		:key="genId(mailbox)"
		v-droppable-mailbox="{
			mainStore: mainStore,
			mailboxId: mailbox.databaseId,
			accountId: mailbox.accountId,
			isValidDropTarget,
		}"
		:allow-collapse="hasSubMailboxes"
		:menu-open.sync="menuOpen"
		:force-menu="true"
		:name="title"
		:to="to"
		:open.sync="showSubMailboxes"
		@update:menuOpen="onMenuToggle">
		<template #icon>
			<div>
				<ImportantIcon v-if="mailbox.isPriorityInbox"
					:size="20" />
				<IconAllInboxes v-else-if="mailbox.id === UNIFIED_INBOX_ID"
					:size="20" />
				<IconInbox v-else-if="mailbox.specialRole === 'inbox' && !mailbox.isPriorityInbox && filter !=='starred'"
					:size="20" />
				<IconFavorite v-else-if="filter === 'starred'"
					:size="20" />
				<IconDraft v-else-if="mailbox.databaseId === account.draftsMailboxId"
					:size="20" />
				<IconSend v-else-if="mailbox.databaseId === account.sentMailboxId"
					:size="20" />
				<IconArchive v-else-if="mailbox.databaseId === account.archiveMailboxId"
					:size="20" />
				<IconDelete v-else-if="mailbox.databaseId === account.trashMailboxId"
					:size="20" />
				<IconJunk v-else-if="mailbox.databaseId === account.junkMailboxId"
					:size="20" />
				<AlarmIcon v-else-if="mailbox.databaseId === account.snoozeMailboxId"
					:size="20" />
				<IconFolderShared v-else-if="mailbox.shared"
					:size="20" />
				<IconFolder v-else
					:size="20" />
			</div>
		</template>
		<!-- actions -->
		<template #actions>
			<ActionText v-if="!account.isUnified && mailbox.specialRole !== 'flagged'"
				:name="mailbox.name">
				<template #icon>
					<IconInfo :title="statsText"
						:size="20" />
				</template>
				{{ statsText }}
			</ActionText>

			<ActionButton v-if="mailbox.specialRole !== 'flagged' && !account.isUnified && hasSeenAcl"
				:name="t('mail', 'Mark all as read')"
				:disabled="loadingMarkAsRead"
				@click="markAsRead">
				<template #icon>
					<IconEmailCheck :size="20" />
				</template>
				{{ t('mail', 'Mark all messages of this folder as read') }}
			</ActionButton>
			<ActionButton v-if="!editing && !account.isUnified && hasDelimiter && mailbox.specialRole !== 'flagged' && hasSubmailboxActionAcl"
				@click="openCreateMailbox">
				<template #icon>
					<IconFolderAdd :size="20" />
				</template>
				{{ t('mail', 'Add subfolder') }}
			</ActionButton>
			<ActionInput v-if="editing"
				:value.sync="createMailboxName"
				@submit.prevent.stop="createMailbox">
				<template #icon>
					<IconFolderAdd :size="20" />
				</template>
			</ActionInput>
			<ActionButton v-if="renameLabel && !hasSubMailboxes && !account.isUnified && hasRenameAcl"
				@click.prevent.stop="openRenameInput">
				<template #icon>
					<IconFolderRename :size="20" />
				</template>
				{{ t('mail', 'Rename') }}
			</ActionButton>
			<ActionInput v-if="renameInput"
				:value.sync="mailboxName"
				@submit.prevent.stop="renameMailbox">
				<template #icon>
					<IconFolderRename :title="t('mail', 'Rename')"
						:size="20" />
				</template>
			</ActionInput>
			<ActionText v-if="showSaving">
				<template #icon>
					<IconLoading :size="20" />
				</template>
				{{ t('mail', 'Saving') }}
			</ActionText>
			<ActionButton v-if="!account.isUnified && hasDelimiter && !mailbox.specialRole && !hasSubMailboxes && hasDeleteAcl"
				:id="genId(mailbox)"
				:close-after-click="true"
				@click.prevent="onOpenMoveModal">
				<template #icon>
					<IconExternal :size="20" />
				</template>
				{{ t('mail', 'Move folder') }}
			</ActionButton>
			<ActionButton v-if="!account.isUnified && mailbox.specialRole !== 'flagged'"
				:disabled="repairing"
				@click="repair">
				<template #icon>
					<IconWrench :size="20" />
				</template>
				{{ t('mail', 'Repair folder') }}
			</ActionButton>
			<ActionButton v-if="debug && !account.isUnified && mailbox.specialRole !== 'flagged'"
				:name="t('mail', 'Clear cache')"
				:disabled="clearingCache"
				@click="clearCache">
				<template #icon>
					<IconFolderSync :size="20" />
				</template>
				{{ t('mail', 'Clear locally cached data, in case there are issues with synchronization.') }}
			</ActionButton>

			<ActionCheckbox v-if="notVirtual"
				:checked="mailbox.isSubscribed"
				:disabled="changeSubscription"
				@update:checked="changeFolderSubscription">
				{{ t('mail', 'Subscribed') }}
			</ActionCheckbox>

			<ActionCheckbox v-if="notVirtual && notInbox"
				:checked="mailbox.syncInBackground"
				:disabled="changingSyncInBackground"
				@update:checked="changeSyncInBackground">
				{{ t('mail', 'Sync in background') }}
			</ActionCheckbox>

			<ActionButton v-if="mailbox.specialRole !== 'flagged' && !account.isUnified && hasClearMailboxAcl"
				:close-after-click="true"
				@click="clearMailbox">
				<template #icon>
					<EraserIcon :size="20" />
				</template>
				{{ t('mail', 'Clear folder') }}
			</ActionButton>

			<ActionButton v-if="!account.isUnified && !mailbox.specialRole && !hasSubMailboxes && hasDeleteAcl"
				@click="deleteMailbox">
				<template #icon>
					<IconDelete :size="20" />
				</template>
				{{ t('mail', 'Delete folder') }}
			</ActionButton>
		</template>
		<template #counter>
			<CounterBubble v-if="showUnreadCounter && subCounter">
				{{ mailbox.unread }}&nbsp;({{ subCounter }})
			</CounterBubble>
			<CounterBubble v-else-if="showUnreadCounter">
				{{ mailbox.unread }}
			</CounterBubble>
		</template>
		<template #extra>
			<MoveMailboxModal v-if="showMoveModal"
				:account="account"
				:mailbox="mailbox"
				@close="onCloseMoveModal" />
		</template>
		<!-- submailboxes -->
		<NavigationMailbox v-for="subMailbox in subMailboxes"
			:key="genId(subMailbox)"
			:account="account"
			:mailbox="subMailbox" />
	</AppNavigationItem>
</template>

<script>

import { NcAppNavigationItem as AppNavigationItem, NcCounterBubble as CounterBubble, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionText as ActionText, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconEmailCheck from 'vue-material-design-icons/EmailCheckOutline.vue'
import IconExternal from 'vue-material-design-icons/OpenInNew.vue'
import IconFolder from 'vue-material-design-icons/FolderOutline.vue'
import IconFolderShared from 'vue-material-design-icons/FolderAccountOutline.vue'
import IconFolderAdd from 'vue-material-design-icons/FolderMultipleOutline.vue'
import IconFavorite from 'vue-material-design-icons/StarOutline.vue'
import IconFolderRename from 'vue-material-design-icons/FolderEditOutline.vue'
import IconFolderSync from 'vue-material-design-icons/FolderSyncOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconInfo from 'vue-material-design-icons/InformationOutline.vue'
import IconDraft from 'vue-material-design-icons/PencilOutline.vue'
import IconArchive from 'vue-material-design-icons/ArchiveArrowDownOutline.vue'
import IconInbox from 'vue-material-design-icons/HomeOutline.vue'
import IconJunk from 'vue-material-design-icons/Fire.vue'
import IconAllInboxes from 'vue-material-design-icons/InboxMultipleOutline.vue'
import EraserIcon from 'vue-material-design-icons/Eraser.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import IconSend from 'vue-material-design-icons/SendOutline.vue'
import IconWrench from 'vue-material-design-icons/Wrench.vue'
import MoveMailboxModal from './MoveMailboxModal.vue'
import { PRIORITY_INBOX_ID, UNIFIED_INBOX_ID } from '../store/constants.js'
import { mailboxHasRights } from '../util/acl.js'
import { clearCache } from '../service/MessageService.js'
import { getMailboxStatus, repairMailbox } from '../service/MailboxService.js'
import logger from '../logger.js'
import { translatePlural as n } from '@nextcloud/l10n'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator.js'
import { showInfo, showError } from '@nextcloud/dialogs'
import { DroppableMailboxDirective as droppableMailbox } from '../directives/drag-and-drop/droppable-mailbox/index.js'
import dragEventBus from '../directives/drag-and-drop/util/dragEventBus.js'
import AlarmIcon from 'vue-material-design-icons/Alarm.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'NavigationMailbox',
	components: {
		AppNavigationItem,
		CounterBubble,
		ActionText,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		IconSend,
		IconDelete,
		IconEmailCheck,
		IconExternal,
		IconFolderAdd,
		IconFolderRename,
		IconFolderSync,
		IconInfo,
		IconAllInboxes,
		IconFavorite,
		IconFolder,
		IconFolderShared,
		IconDraft,
		IconArchive,
		IconJunk,
		IconInbox,
		IconWrench,
		EraserIcon,
		ImportantIcon,
		IconLoading,
		MoveMailboxModal,
		AlarmIcon,
	},
	directives: {
		droppableMailbox,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		mailbox: {
			type: Object,
			required: true,
		},
		filter: {
			type: String,
			default: '',
			required: false,
		},
	},
	data() {
		return {
			debug: window?.OC?.debug || false,
			mailboxStats: undefined,
			loadingMarkAsRead: false,
			clearingCache: false,
			showSaving: false,
			changeSubscription: false,
			changingSyncInBackground: false,
			editing: false,
			showSubMailboxes: false,
			menuOpen: false,
			renameLabel: true,
			renameInput: false,
			mailboxName: this.mailbox.displayName,
			showMoveModal: false,
			hasDelimiter: !!this.mailbox.delimiter,
			UNIFIED_INBOX_ID,
			createMailboxName: '',
			repairing: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		visible() {
			return (
				(this.account.showSubscribedOnly === false
				|| (this.mailbox.attributes && this.mailbox.attributes.includes('\\subscribed'))) && this.isUnifiedButOnlyInbox
			)
		},
		notInbox() {
			return this.mailbox.name.toLowerCase() !== 'inbox'
		},
		notVirtual() {
			return !this.account.isUnified && this.mailbox.specialRole !== 'flagged' && !this.filter
		},
		title() {
			if (this.filter === 'starred') {
				// Little hack to trick the translation logic into a different path
				return translateMailboxName({
					...this.mailbox,
					specialUse: ['flagged'],
				})
			}
			return translateMailboxName(this.mailbox)
		},
		to() {
			return {
				name: 'mailbox',
				params: {
					mailboxId: this.mailbox.databaseId,
					filter: this.filter ? this.filter : undefined,
				},
			}
		},
		hasSubMailboxes() {
			return this.subMailboxes.length > 0
		},
		subMailboxes() {
			return this.mainStore.getSubMailboxes(this.mailbox.databaseId)
		},
		statsText() {
			if (this.mailboxStats && 'total' in this.mailboxStats && 'unread' in this.mailboxStats) {
				if (this.mailboxStats.unread === 0) {
					return n('mail', '{total} message', '{total} messages', this.mailboxStats.total, {
						total: this.mailboxStats.total,
					})
				} else {
					return n(
						'mail',
						'{unread} unread of {total}',
						'{unread} unread of {total}',
						this.mailboxStats.unread,
						{
							total: this.mailboxStats.total,
							unread: this.mailboxStats.unread,
						},
					)
				}
			}
			return t('mail', 'Loading …')
		},
		isDroppableSpecialMailbox() {
			if (this.filter === 'starred') {
				return false
			}
			return ![
				this.account.draftsMailboxId,
				this.account.sentMailboxId,
			].includes(this.mailbox.databaseId)
		},
		isActive() {
			return this.$route.params.mailboxId === this.mailbox.databaseId
		},
		isValidDropTarget() {
			if (this.isActive || !this.hasInsertAcl) {
				return false
			}
			return this.isDroppableSpecialMailbox || (!this.mailbox.specialRole && !this.account.isUnified)
		},
		isUnifiedButOnlyInbox() {
			if (!this.mailbox.isUnified) {
				return true
			}
			return this.mailbox.specialUse.includes('inbox') && this.mainStore.getAccounts.length > 2
		},
		showUnreadCounter() {
			if (this.filter === 'starred' || this.mailbox.specialRole === 'trash') {
				return false
			}
			return this.mailbox.unread > 0 || this.subCounter > 0
		},
		subCounter() {
			return this.subMailboxes.reduce((carry, mb) => carry + mb.unread, 0)
		},
		hasRenameAcl() {
			if (!this.mailbox.myAcls) {
				return true
			}
			const parent = this.mainStore.getParentMailbox(this.mailbox.databaseId)
			if (!parent || !parent.myAcls) {
				return mailboxHasRights(this.mailbox, 'x')
			}

			return mailboxHasRights(this.mailbox, 'x')
				&& mailboxHasRights(parent, 'k')
		},
		hasInsertAcl() {
			return mailboxHasRights(this.mailbox, 'i')
		},
		hasSeenAcl() {
			return mailboxHasRights(this.mailbox, 's')
		},
		hasSubmailboxActionAcl() {
			return mailboxHasRights(this.mailbox, 'k')
		},
		hasDeleteAcl() {
			return mailboxHasRights(this.mailbox, 'x')
		},
		hasClearMailboxAcl() {
			return mailboxHasRights(this.mailbox, 'te')
		},
	},
	mounted() {
		dragEventBus.on('drag-start', this.onDragStart)
		dragEventBus.on('drag-end', this.onDragEnd)
		dragEventBus.on('envelopes-moved', this.onEnvelopesMoved)
	},
	beforeDestroy() {
		dragEventBus.off('drag-start', this.onDragStart)
		dragEventBus.off('drag-end', this.onDragEnd)
		dragEventBus.off('envelopes-moved', this.onEnvelopesMoved)
	},
	methods: {
		/**
		 * Generate unique key id for a specific mailbox
		 *
		 * @param {object} mailbox the mailbox to gen id for
		 * @return {string}
		 */
		genId(mailbox) {
			return 'folder-' + mailbox.databaseId
		},

		/**
		 * On menu toggle, fetch stats
		 *
		 * @param {boolean} open menu opened state
		 */
		onMenuToggle(open) {
			if (open) {
				this.fetchMailboxStats()
			}
		},

		/**
		 * Fetch mailbox unread/read stats
		 */
		async fetchMailboxStats() {
			this.mailboxStats = null
			if (this.account.isUnified || this.mailbox.specialRole === 'flagged') {
				return
			}

			try {
				const stats = await getMailboxStatus(this.mailbox.databaseId)
				logger.debug(`loaded mailbox stats for ${this.mailbox.databaseId}`, { stats })
				this.mailboxStats = stats
			} catch (error) {
				this.mailboxStats = { error: true }
				logger.error(`could not load mailbox stats for ${this.mailbox.databaseId}`, error)
			}
		},

		async createMailbox(e) {
			this.editing = true
			const name = this.createMailboxName
			const withPrefix = this.mailbox.name + this.mailbox.delimiter + name
			logger.info(`creating mailbox ${withPrefix} as submailbox of ${this.mailbox.databaseId}`)
			this.menuOpen = false
			try {
				await this.mainStore.createMailbox({
					account: this.account,
					name: withPrefix,
				})
			} catch (error) {
				logger.error(`could not create mailbox ${withPrefix}`, { error })
				throw error
			} finally {
				this.editing = false
				this.showSaving = false
			}
			logger.info(`mailbox ${withPrefix} created`)
			this.showSubMailboxes = true
		},
		openCreateMailbox() {
			this.editing = true
			this.showSaving = false
		},
		markAsRead() {
			this.loadingMarkAsRead = true

			this.mainStore.markMailboxRead({
				accountId: this.account.id,
				mailboxId: this.mailbox.databaseId,
			})
				.then(() => logger.info(`mailbox ${this.mailbox.databaseId} marked as read`))
				.catch((error) => logger.error(`could not mark mailbox ${this.mailbox.databaseId} as read`, { error }))
				.then(() => (this.loadingMarkAsRead = false))
		},
		async changeFolderSubscription(subscribed) {
			try {
				this.changeSubscription = true

				await this.mainStore.changeMailboxSubscription({
					mailbox: this.mailbox,
					subscribed,
				})
			} catch (error) {
				logger.error(`could not update subscription of mailbox ${this.mailbox.databaseId}`, { error })
				throw error
			} finally {
				this.changeSubscription = false
			}
		},
		async changeSyncInBackground(syncInBackground) {
			try {
				this.changingSyncInBackground = true

				await this.mainStore.patchMailbox({
					mailbox: this.mailbox,
					attributes: {
						syncInBackground,
					},
				})
			} catch (error) {
				logger.error(`could not update background sync flag of mailbox ${this.mailbox.databaseId}`, { error })
				throw error
			} finally {
				this.changingSyncInBackground = false
			}
		},
		async clearCache() {
			try {
				this.clearingCache = true
				logger.debug('clearing message cache', {
					accountId: this.account.id,
					mailboxId: this.mailbox.databaseId,
				})

				await clearCache(this.account.id, this.mailbox.databaseId)

				// TODO: there might be a nicer way to handle this
				window.location.reload(false)
			} finally {
				this.clearCache = false
			}
		},
		clearMailbox() {
			const id = this.mailbox.databaseId
			OC.dialogs.confirmDestructive(
				t('mail', 'All messages in mailbox will be deleted.'),
				t('mail', 'Clear mailbox {name}', { name: this.mailbox.displayName }),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('mail', 'Clear folder'),
					confirmClasses: 'error',
					cancel: t('mail', 'Cancel'),
				},
				(result) => {
					if (result) {
						return this.mainStore.clearMailbox({ mailbox: this.mailbox })
							.then(() => {
								logger.info(`mailbox ${id} cleared`)
							})
							.catch((error) => logger.error('could not clear folder', { error }))
					}
				},
			)
		},
		deleteMailbox() {
			const id = this.mailbox.databaseId
			logger.info('delete folder', { mailbox: this.mailbox })
			OC.dialogs.confirmDestructive(
				t('mail', 'The folder and all messages in it will be deleted.'),
				t('mail', 'Delete folder'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('mail', 'Delete folder {name}', { name: this.mailbox.displayName }),
					confirmClasses: 'error',
					cancel: t('mail', 'Cancel'),
				},
				(result) => {
					if (result) {
						return this.mainStore.deleteMailbox({ mailbox: this.mailbox })
							.then(() => {
								logger.info(`mailbox ${id} deleted`)
								if (parseInt(this.$route.params.mailboxId, 10) === this.mailbox.databaseId) {
									this.$router.push({
										name: 'mailbox',
										params: {
											mailboxId: PRIORITY_INBOX_ID,
										},
									})
								}
							})
							.catch((error) => logger.error('could not delete folder', { error }))
					}
				},
			)
		},
		async renameMailbox() {
			this.renameInput = false
			this.showSaving = true

			try {
				let newName = this.mailboxName
				if (this.mailbox.path) {
					newName = this.mailbox.path + this.mailbox.delimiter + newName
				}
				await this.mainStore.renameMailbox({
					account: this.account,
					mailbox: this.mailbox,
					newName,
				})
				this.renameLabel = true
				this.renameInput = false
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the mailbox.'))
				console.error(error)
			} finally {
				this.showSaving = false
			}
		},
		openRenameInput() {
			// Hide label and show input
			this.renameLabel = false
			this.renameInput = true
			this.showSaving = false
		},
		onOpenMoveModal() {
			this.showMoveModal = true
		},
		onCloseMoveModal() {
			this.showMoveModal = false
		},
		onDragStart({ accountId }) {
			if (accountId !== this.mailbox.accountId) {
				return
			}
			this.mainStore.expandAccountMutation(accountId)
			this.showSubMailboxes = true
		},
		onDragEnd({ accountId }) {
			if (accountId !== this.mailbox.accountId) {
				return
			}
			this.showSubMailboxes = false
		},
		onEnvelopesMoved({ mailboxId, movedEnvelopes }) {
			if (this.mailbox.databaseId !== mailboxId) {
				return
			}
			const openedMessageHasBeenMoved = movedEnvelopes.find((movedEnvelope) => {
				return movedEnvelope.envelopeId === this.$route.params.threadId
			})
			// navigate to the mailbox root
			// if the currently displayed message has been moved
			if (this.$route.name === 'message' && openedMessageHasBeenMoved) {
				this.$router.push({
					name: 'mailbox',
					params: {
						mailboxId: this.$route.params.mailboxId,
						filter: this.$route.params?.filter,
					},
				})
			}
		},
		/**
		 * Delete all vanished emails that are still cached.
		 *
		 * @return {Promise<void>}
		 */
		async repair() {
			this.repairing = true

			const mailboxId = this.mailbox.databaseId
			try {
				await repairMailbox(mailboxId)

				// Reload the page to start with a clean mailbox state
				await this.$router.push({
					name: 'mailbox',
					params: {
						mailboxId: this.$route.params.mailboxId,
					},
				})
				window.location.reload()
			} catch (error) {
				// Only reset state in case of an error because the page will be reloaded anyway
				this.repairing = false

				// Handle rate limit: 429 Too Many Requests
				// Ref https://axios-http.com/docs/handling_errors
				if (error.response?.status === 429) {
					showError(t('mail', 'Please wait 10 minutes before repairing again'))
				} else {
					throw error
				}
			}
		},
	},
}
</script>
<style lang="scss" scoped>
.counter-bubble__counter {
	max-width: initial;
}
</style>
