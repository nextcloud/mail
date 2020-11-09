<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<AppNavigationItem
		v-if="visible"
		:id="genId(mailbox)"
		:key="genId(mailbox)"
		:allow-collapse="true"
		:menu-open.sync="menuOpen"
		:force-menu="true"
		:icon="icon"
		:title="title"
		:to="to"
		:open.sync="showSubMailboxes"
		@update:menuOpen="onMenuToggle">
		<!-- actions -->
		<template slot="actions">
			<template>
				<ActionText
					v-if="!account.isUnified && mailbox.specialRole !== 'flagged'"
					icon="icon-info"
					:title="mailbox.name">
					{{ statsText }}
				</ActionText>

				<ActionButton
					v-if="mailbox.specialRole !== 'flagged' && !account.isUnified"
					icon="icon-mail"
					:title="t('mail', 'Mark all as read')"
					:disabled="loadingMarkAsRead"
					@click="markAsRead">
					{{ t('mail', 'Mark all messages of this mailbox as read') }}
				</ActionButton>
				<ActionButton
					v-if="!editing && top && !account.isUnified && mailbox.specialRole !== 'flagged'"
					icon="icon-folder"
					@click="openCreateMailbox">
					{{ t('mail', 'Add subfolder') }}
				</ActionButton>
				<ActionInput v-if="editing" icon="icon-folder" @submit.prevent.stop="createMailbox" />
				<ActionButton
					v-if="renameLabel && !hasSubMailboxes && !account.isUnified"
					icon="icon-rename"
					@click.prevent.stop="openRenameInput">
					{{ t('mail', 'Edit name') }}
				</ActionButton>
				<ActionInput
					v-if="renameInput"
					icon="icon-rename"
					:value.sync="mailboxName"
					@submit.prevent.stop="renameMailbox" />
				<ActionText v-if="showSaving" icon="icon-loading-small">
					{{ t('mail', 'Saving') }}
				</ActionText>
				<ActionButton v-if="!account.isUnified && !mailbox.specialRole && !hasSubMailboxes"
					:id="genId(mailbox)"
					icon="icon-external"
					:close-after-click="true"
					@click.prevent="onOpenMoveModal">
					{{ t('mail', 'Move') }}
				</ActionButton>
				<ActionButton
					v-if="debug && !account.isUnified && mailbox.specialRole !== 'flagged'"
					icon="icon-settings"
					:title="t('mail', 'Clear cache')"
					:disabled="clearingCache"
					@click="clearCache">
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

				<ActionButton v-if="!account.isUnified && !mailbox.specialRole && !hasSubMailboxes" icon="icon-delete" @click="deleteMailbox">
					{{ t('mail', 'Delete folder') }}
				</ActionButton>
			</template>
		</template>
		<AppNavigationCounter v-if="mailbox.unread" slot="counter">
			{{ mailbox.unread }}
		</AppNavigationCounter>
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
			:mailbox="subMailbox"
			:top="false" />
	</AppNavigationItem>
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationCounter from '@nextcloud/vue/dist/Components/AppNavigationCounter'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import MoveMailboxModal from './MoveMailboxModal'

import { clearCache } from '../service/MessageService'
import { getMailboxStatus } from '../service/MailboxService'
import logger from '../logger'
import { translatePlural as n } from '@nextcloud/l10n'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator'
import { showInfo } from '@nextcloud/dialogs'

export default {
	name: 'NavigationMailbox',
	components: {
		AppNavigationItem,
		AppNavigationCounter,
		ActionText,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		MoveMailboxModal,
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
		top: {
			type: Boolean,
			default: true,
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

		}
	},
	computed: {
		visible() {
			return (
				this.account.showSubscribedOnly === false
				|| (this.mailbox.attributes && this.mailbox.attributes.includes('\\subscribed'))
			)
		},
		notInbox() {
			return this.mailbox.name.toLowerCase() !== 'inbox'
		},
		notVirtual() {
			return !this.account.isUnified && this.mailbox.specialRole !== 'flagged'
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
		icon() {
			if (this.filter === 'starred') {
				return 'icon-flagged'
			} else if (this.mailbox.isPriorityInbox) {
				return 'icon-important'
			}
			return this.mailbox.specialRole ? 'icon-' + this.mailbox.specialRole : 'icon-folder'
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
	},
	methods: {
		/**
		 * Generate unique key id for a specific mailbox
		 * @param {Object} mailbox the mailbox to gen id for
		 * @returns {string}
		 */
		genId(mailbox) {
			return 'mailbox-' + mailbox.databaseId
		},

		/**
		 * On menu toggle, fetch stats
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
			const name = e.target.elements[1].value
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
		deleteMailbox() {
			const id = this.mailbox.databaseId
			logger.info('delete mailbox', { mailbox: this.mailbox })
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
						return this.$store
							.dispatch('deleteMailbox', { mailbox: this.mailbox })
							.then(() => {
								logger.info(`mailbox ${id} deleted`)
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
	},
}
</script>
