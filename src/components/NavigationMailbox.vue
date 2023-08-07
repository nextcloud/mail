<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license AGPL-3.0-or-later
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
	<AppNavigationItem
		v-if="visible"
		:id="genId(mailbox)"
		:key="genId(mailbox)"
		v-droppable-mailbox="{
			mailboxId: mailbox.databaseId,
			accountId: mailbox.accountId,
			isValidDropTarget,
		}"
		:allow-collapse="true"
		:menu-open.sync="menuOpen"
		:force-menu="true"
		:title="title"
		:to="to"
		:open.sync="showSubMailboxes"
		@update:menuOpen="onMenuToggle">
		<template #icon>
			<div class="sidebar-opacity-icon">
				<ImportantIcon v-if="mailbox.isPriorityInbox"
					:size="20" />
				<IconAllInboxes
					v-else-if="mailbox.id === UNIFIED_INBOX_ID"
					:size="20" />
				<IconInbox
					v-else-if="mailbox.specialRole === 'inbox' && !mailbox.isPriorityInbox && filter !=='starred'"
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
				<IconFolderShared v-else-if="mailbox.shared"
					:size="20" />
				<IconFolder v-else
					:size="20" />
			</div>
		</template>
		<!-- actions -->
		<template slot="actions">
			<ActionText
				v-if="!account.isUnified && mailbox.specialRole !== 'flagged'"
				:title="mailbox.name">
				<template #icon>
					<IconInfo
						:title="statsText"
						:size="20" />
				</template>
				{{ statsText }}
			</ActionText>

			<ActionButton
				v-if="mailbox.specialRole !== 'flagged' && !account.isUnified && hasSeenAcl"
				:title="t('mail', 'Mark all as read')"
				:disabled="loadingMarkAsRead"
				@click="markAsRead">
				<template #icon>
					<IconEmailCheck
						:size="20" />
				</template>
				{{ t('mail', 'Mark all messages of this mailbox as read') }}
			</ActionButton>
			<ActionButton
				v-if="!editing && !account.isUnified && hasDelimiter && mailbox.specialRole !== 'flagged' && hasSubmailboxActionAcl"
				@click="openCreateMailbox">
				<template #icon>
					<IconFolderAdd
						:size="20" />
				</template>
				{{ t('mail', 'Add submailbox') }}
			</ActionButton>
			<ActionInput
				v-if="editing"
				:value.sync="createMailboxName"
				@submit.prevent.stop="createMailbox">
				<template #icon>
					<IconFolderAdd
						:size="20" />
				</template>
			</ActionInput>
			<ActionButton
				v-if="renameLabel && !hasSubMailboxes && !account.isUnified && hasRenameAcl"
				@click.prevent.stop="openRenameInput">
				<template #icon>
					<IconFolderRename
						:size="20" />
				</template>
				{{ t('mail', 'Edit name') }}
			</ActionButton>
			<ActionInput
				v-if="renameInput"
				:value.sync="mailboxName"
				@submit.prevent.stop="renameMailbox">
				<template #icon>
					<IconFolderRename
						:title="t('mail', 'Edit name')"
						:size="20" />
				</template>
			</ActionInput>
			<ActionText v-if="showSaving">
				<template #icon>
					<IconLoading
						:size="20" />
				</template>
				{{ t('mail', 'Saving') }}
			</ActionText>
			<ActionButton v-if="!account.isUnified && hasDelimiter && !mailbox.specialRole && !hasSubMailboxes && hasDeleteAcl"
				:id="genId(mailbox)"
				:close-after-click="true"
				@click.prevent="onOpenMoveModal">
				<template #icon>
					<IconExternal
						:size="20" />
				</template>
				{{ t('mail', 'Move mailbox') }}
			</ActionButton>
			<ActionButton
				v-if="debug && !account.isUnified && mailbox.specialRole !== 'flagged'"
				:title="t('mail', 'Clear cache')"
				:disabled="clearingCache"
				@click="clearCache">
				<template #icon>
					<IconFolderSync
						:size="20" />
				</template>
				{{ t('mail', 'Clear locally cached data, in case there are issues with synchronization.') }}
			</ActionButton>

			<ActionCheckbox
				v-if="notVirtual"
				:checked="isSubscribed"
				:disabled="changeSubscription"
				@update:checked="changeFolderSubscription">
				{{ t('mail', 'Subscribed') }}
			</ActionCheckbox>

			<ActionCheckbox
				v-if="notVirtual && notInbox"
				:checked="mailbox.syncInBackground"
				:disabled="changingSyncInBackground"
				@update:checked="changeSyncInBackground">
				{{ t('mail', 'Sync in background') }}
			</ActionCheckbox>

			<ActionButton
				v-if="mailbox.specialRole !== 'flagged' && !account.isUnified && hasClearMailboxAcl"
				:close-after-click="true"
				@click="clearMailbox">
				<template #icon>
					<EraserVariant :size="20" />
				</template>
				{{ t('mail', 'Clear mailbox') }}
			</ActionButton>

			<ActionButton v-if="!account.isUnified && !mailbox.specialRole && !hasSubMailboxes && hasDeleteAcl"
				@click="deleteMailbox">
				<template #icon>
					<IconDelete
						:size="20" />
				</template>
				{{ t('mail', 'Delete mailbox') }}
			</ActionButton>
		</template>
		<CounterBubble v-if="showUnreadCounter && subCounter" slot="counter">
			{{ mailbox.unread }}&nbsp;({{ subCounter }})
		</CounterBubble>
		<CounterBubble v-else-if="showUnreadCounter" slot="counter">
			{{ mailbox.unread }}
		</CounterBubble>
		<template slot="extra">
			<MoveMailboxModal v-if="showMoveModal"
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
	</AppNavigationItem>
</template>

<script>

import { NcAppNavigationItem as AppNavigationItem, NcCounterBubble as CounterBubble, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionText as ActionText, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconEmailCheck from 'vue-material-design-icons/EmailCheck'
import IconExternal from 'vue-material-design-icons/OpenInNew'
import IconFolder from 'vue-material-design-icons/Folder'
import IconFolderShared from 'vue-material-design-icons/FolderAccount'
import IconFolderAdd from 'vue-material-design-icons/FolderMultiple'
import IconFavorite from 'vue-material-design-icons/Star'
import IconFolderRename from 'vue-material-design-icons/FolderEdit'
import IconFolderSync from 'vue-material-design-icons/FolderSync'
import IconDelete from 'vue-material-design-icons/Delete'
import IconInfo from 'vue-material-design-icons/Information'
import IconDraft from 'vue-material-design-icons/Pencil'
import IconArchive from 'vue-material-design-icons/PackageDown'
import IconInbox from 'vue-material-design-icons/Home'
import IconJunk from 'vue-material-design-icons/Fire'
import IconAllInboxes from 'vue-material-design-icons/InboxMultiple'
import EraserVariant from 'vue-material-design-icons/EraserVariant'
import ImportantIcon from './icons/ImportantIcon'
import IconSend from 'vue-material-design-icons/Send'
import MoveMailboxModal from './MoveMailboxModal'
import { PRIORITY_INBOX_ID, UNIFIED_INBOX_ID } from '../store/constants'
import { mailboxHasRights } from '../util/acl'
import { clearCache } from '../service/MessageService'
import { getMailboxStatus } from '../service/MailboxService'
import logger from '../logger'
import { translatePlural as n } from '@nextcloud/l10n'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator'
import { showInfo } from '@nextcloud/dialogs'
import { DroppableMailboxDirective as droppableMailbox } from '../directives/drag-and-drop/droppable-mailbox'
import dragEventBus from '../directives/drag-and-drop/util/dragEventBus'

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
		EraserVariant,
		ImportantIcon,
		IconLoading,
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
		}
	},
	computed: {
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
			return this.$store.getters.getSubMailboxes(this.mailbox.databaseId)
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
						}
					)
				}
			}
			return t('mail', 'Loading …')
		},
		isSubscribed() {
			return this.mailbox.attributes && this.mailbox.attributes.includes('\\subscribed')
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
			return this.mailbox.specialUse.includes('inbox') && this.$store.getters.accounts.length > 2
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
			const parent = this.$store.getters.getParentMailbox(this.mailbox.databaseId)
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
		dragEventBus.$on('drag-start', this.onDragStart)
		dragEventBus.$on('drag-end', this.onDragEnd)
		dragEventBus.$on('envelopes-moved', this.onEnvelopesMoved)
	},
	beforeDestroy() {
		dragEventBus.$off('drag-start', this.onDragStart)
		dragEventBus.$off('drag-end', this.onDragEnd)
		dragEventBus.$off('envelopes-moved', this.onEnvelopesMoved)
	},
	methods: {
		/**
		 * Generate unique key id for a specific mailbox
		 *
		 * @param {object} mailbox the mailbox to gen id for
		 * @return {string}
		 */
		genId(mailbox) {
			return 'mailbox-' + mailbox.databaseId
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
				await this.$store.dispatch('createMailbox', {
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

			this.$store
				.dispatch('markMailboxRead', {
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

				await this.$store.dispatch('changeMailboxSubscription', {
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

				await this.$store.dispatch('patchMailbox', {
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
					confirm: t('mail', 'Clear mailbox'),
					confirmClasses: 'error',
					cancel: t('mail', 'Cancel'),
				},
				(result) => {
					if (result) {
						return this.$store
							.dispatch('clearMailbox', { mailbox: this.mailbox })
							.then(() => {
								logger.info(`mailbox ${id} cleared`)
							})
							.catch((error) => logger.error('could not clear mailbox', { error }))
					}
				}
			)
		},
		deleteMailbox() {
			const id = this.mailbox.databaseId
			logger.info('delete mailbox', { mailbox: this.mailbox })
			OC.dialogs.confirmDestructive(
				t('mail', 'The mailbox and all messages in it will be deleted.'),
				t('mail', 'Delete mailbox'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('mail', 'Delete mailbox {name}', { name: this.mailbox.displayName }),
					confirmClasses: 'error',
					cancel: t('mail', 'Cancel'),
				},
				(result) => {
					if (result) {
						return this.$store
							.dispatch('deleteMailbox', { mailbox: this.mailbox })
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
							.catch((error) => logger.error('could not delete mailbox', { error }))
					}
				}
			)
		},
		async renameMailbox() {
			this.renameInput = false
			this.showSaving = false

			try {
				await this.$store.dispatch('renameMailbox', {
					account: this.account,
					mailbox: this.mailbox,
					newName: this.mailboxName,
				})
				this.renameLabel = true
				this.renameInput = false
				this.showSaving = false
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the mailbox.'))
				console.error(error)
				this.renameLabel = false
				this.renameInput = false
				this.showSaving = true
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
			this.$store.commit('expandAccount', accountId)
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
	},
}
</script>
<style lang="scss" scoped>
.sidebar-opacity-icon {
	opacity: .7;
	&:hover {
	opacity: 1;
	}
}
.counter-bubble__counter {
	max-width: initial;
}
</style>
