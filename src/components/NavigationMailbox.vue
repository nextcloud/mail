<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigationItem
		v-if="visible"
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
		<template #icon="{ active }">
			<div>
				<MailboxIcon
					:mailbox="mailbox"
					:account="account"
					:filter="filter"
					:active="active" />
			</div>
		</template>
		<!-- actions -->
		<template #actions>
			<NcActionText
				v-if="!account.isUnified && mailbox.specialRole !== 'flagged'"
				:name="mailbox.name">
				<template #icon>
					<IconInfo
						:title="statsText"
						:size="20" />
				</template>
				{{ statsText }}
			</NcActionText>

			<NcActionButton
				v-if="mailbox.specialRole !== 'flagged' && !account.isUnified && hasSeenAcl"
				:name="t('mail', 'Mark all as read')"
				:disabled="loadingMarkAsRead"
				@click="markAsRead">
				<template #icon>
					<IconEmailCheck :size="20" />
				</template>
			</NcActionButton>
			<NcActionButton
				v-if="subfolderLabel && !account.isUnified && hasDelimiter && mailbox.specialRole !== 'flagged' && hasSubmailboxActionAcl"
				@click="openCreateMailbox">
				<template #icon>
					<IconAdd :size="20" />
				</template>
				{{ t('mail', 'Add subfolder') }}
			</NcActionButton>
			<NcActionInput
				v-if="subfolderInput"
				:value.sync="createMailboxName"
				@submit.prevent.stop="createMailbox">
				<template #icon>
					<IconAdd :size="20" />
				</template>
			</NcActionInput>
			<NcActionText v-if="subfolderSaving">
				<template #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('mail', 'Saving') }}
			</NcActionText>
			<NcActionButton
				v-if="renameLabel && !hasSubMailboxes && !account.isUnified && hasRenameAcl"
				@click.prevent.stop="openRenameInput">
				<template #icon>
					<IconEdit :size="20" />
				</template>
				{{ t('mail', 'Rename') }}
			</NcActionButton>
			<NcActionInput
				v-if="renameInput"
				:value.sync="mailboxName"
				@submit.prevent.stop="renameMailbox">
				<template #icon>
					<IconEdit
						:title="t('mail', 'Rename')"
						:size="20" />
				</template>
			</NcActionInput>
			<NcActionText v-if="renameSaving">
				<template #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('mail', 'Saving') }}
			</NcActionText>
			<NcActionButton
				v-if="!account.isUnified && hasDelimiter && !mailbox.specialRole && !hasSubMailboxes && hasDeleteAcl"
				:id="genId(mailbox)"
				:close-after-click="true"
				@click.prevent="onOpenMoveModal">
				<template #icon>
					<IconExternal :size="20" />
				</template>
				{{ t('mail', 'Move folder') }}
			</NcActionButton>
			<NcActionButton
				v-if="!account.isUnified && mailbox.specialRole !== 'flagged'"
				:disabled="repairing"
				@click="repair">
				<template #icon>
					<IconWrench :size="20" />
				</template>
				{{ t('mail', 'Repair folder') }}
			</NcActionButton>
			<NcActionButton
				v-if="debug && !account.isUnified && mailbox.specialRole !== 'flagged'"
				:name="t('mail', 'Clear cache')"
				:disabled="clearingCache"
				@click="clearCache">
				<template #icon>
					<IconFolderSync :size="20" />
				</template>
				{{ t('mail', 'Clear locally cached data, in case there are issues with synchronization.') }}
			</NcActionButton>

			<NcActionCheckbox
				v-if="notVirtual"
				:checked="mailbox.isSubscribed"
				:disabled="changeSubscription"
				@update:checked="changeFolderSubscription">
				{{ t('mail', 'Subscribed') }}
			</NcActionCheckbox>

			<NcActionCheckbox
				v-if="notVirtual && notInbox"
				:checked="mailbox.syncInBackground"
				:disabled="changingSyncInBackground"
				@update:checked="changeSyncInBackground">
				{{ t('mail', 'Sync in background') }}
			</NcActionCheckbox>

			<NcActionButton
				v-if="mailbox.specialRole !== 'flagged' && !account.isUnified && hasClearMailboxAcl"
				:close-after-click="true"
				@click="clearMailbox">
				<template #icon>
					<IconDeleteOutline :size="20" />
				</template>
				{{ t('mail', 'Delete all messages') }}
			</NcActionButton>

			<NcActionButton
				v-if="!account.isUnified && !mailbox.specialRole && !hasSubMailboxes && hasDeleteAcl"
				@click="deleteMailbox">
				<template #icon>
					<IconDeleteOutline :size="20" />
				</template>
				{{ t('mail', 'Delete folder') }}
			</NcActionButton>
		</template>
		<template #counter>
			<NcCounterBubble v-if="showUnreadCounter && subCounter">
				{{ mailbox.unread }}&nbsp;({{ subCounter }})
			</NcCounterBubble>
			<NcCounterBubble v-else-if="showUnreadCounter">
				{{ mailbox.unread }}
			</NcCounterBubble>
		</template>
		<template #extra>
			<MoveMailboxModal
				v-if="showMoveModal"
				:account="account"
				:mailbox="mailbox"
				@close="onCloseMoveModal" />
		</template>
		<!-- submailboxes -->
		<NavigationMailbox
			v-for="subMailbox in subMailboxes"
			:key="genId(subMailbox)"
			:account="account"
			:mailbox="subMailbox" />
	</NcAppNavigationItem>
</template>

<script>

import { showError, showInfo } from '@nextcloud/dialogs'
import { n } from '@nextcloud/l10n'
import { NcActionButton, NcActionCheckbox, NcActionInput, NcActionText, NcAppNavigationItem, NcCounterBubble, NcLoadingIcon } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import IconEmailCheck from 'vue-material-design-icons/EmailCheckOutline.vue'
import IconFolderSync from 'vue-material-design-icons/FolderSyncOutline.vue'
import IconInfo from 'vue-material-design-icons/InformationOutline.vue'
import IconExternal from 'vue-material-design-icons/OpenInNew.vue'
import IconEdit from 'vue-material-design-icons/PencilOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconDeleteOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import IconWrench from 'vue-material-design-icons/Wrench.vue'
import MailboxIcon from './icons/MailboxIcon.vue'
import MoveMailboxModal from './MoveMailboxModal.vue'
import { DroppableMailboxDirective as droppableMailbox } from '../directives/drag-and-drop/droppable-mailbox/index.js'
import dragEventBus from '../directives/drag-and-drop/util/dragEventBus.js'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator.js'
import logger from '../logger.js'
import { getMailboxStatus, repairMailbox } from '../service/MailboxService.js'
import { clearCache } from '../service/MessageService.js'
import { PRIORITY_INBOX_ID } from '../store/constants.js'
import useMainStore from '../store/mainStore.js'
import { mailboxHasRights } from '../util/acl.js'

export default {
	name: 'NavigationMailbox',
	components: {
		NcAppNavigationItem,
		NcCounterBubble,
		NcActionText,
		NcActionButton,
		NcActionCheckbox,
		NcActionInput,
		IconDeleteOutline,
		IconEmailCheck,
		IconExternal,
		IconAdd,
		IconEdit,
		IconFolderSync,
		IconInfo,
		IconWrench,
		MailboxIcon,
		MoveMailboxModal,
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
			changeSubscription: false,
			changingSyncInBackground: false,
			subfolderLabel: true,
			subfolderInput: false,
			subfolderSaving: false,
			showSubMailboxes: false,
			wasExpandedBeforeDrag: false,
			menuOpen: false,
			renameLabel: true,
			renameInput: false,
			renameSaving: false,
			mailboxName: this.mailbox.displayName,
			showMoveModal: false,
			hasDelimiter: !!this.mailbox.delimiter,
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
			return t('mail', 'Loading …')
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
			} else {
				if (!this.renameSaving) {
					this.renameLabel = true
					this.renameInput = false
				}

				if (!this.subfolderSaving) {
					this.subfolderLabel = true
					this.subfolderInput = false
				}
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
			this.subfolderInput = false
			this.subfolderSaving = true
			const name = this.createMailboxName
			const withPrefix = this.mailbox.name + this.mailbox.delimiter + name
			logger.info(`creating mailbox ${withPrefix} as submailbox of ${this.mailbox.databaseId}`)

			try {
				await this.mainStore.createMailbox({
					account: this.account,
					name: withPrefix,
				})
			} catch (error) {
				logger.error(`could not create mailbox ${withPrefix}`, { error })
				throw error
			} finally {
				this.menuOpen = false
				this.subfolderLabel = true
				this.subfolderSaving = false
			}

			logger.info(`mailbox ${withPrefix} created`)
			this.showSubMailboxes = true
		},

		openCreateMailbox() {
			this.subfolderLabel = false
			this.createMailboxName = ''
			this.subfolderInput = true
			this.subfolderSaving = false
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
			this.renameSaving = true

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
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the mailbox.'))
				logger.error('could not rename mailbox', { error })
			} finally {
				this.renameSaving = false
				this.renameLabel = true
			}
		},

		openRenameInput() {
			// Hide label and show input
			this.renameLabel = false
			this.mailboxName = this.mailbox.displayName
			this.renameInput = true
			this.renameSaving = false
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
			this.wasExpandedBeforeDrag = this.showSubMailboxes
			this.mainStore.expandAccountMutation(accountId)
			this.showSubMailboxes = true
		},

		onDragEnd({ accountId }) {
			if (accountId !== this.mailbox.accountId) {
				return
			}
			this.showSubMailboxes = this.wasExpandedBeforeDrag
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
					logger.error('mailbox repair rate-limited', { error })
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

:deep(.action-item__menutoggle) {
	background-color: transparent !important;
}
</style>
