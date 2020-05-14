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
		:id="genId(folder)"
		:key="genId(folder)"
		:allow-collapse="true"
		:force-menu="true"
		:icon="icon"
		:title="title"
		:to="to"
		@update:menuOpen="onMenuToggle"
	>
		<!-- actions -->
		<template slot="actions">
			<template v-if="top">
				<ActionText
					v-if="!account.isUnified && folder.specialRole !== 'flagged'"
					icon="icon-info"
					:title="folderId"
				>
					{{ statsText }}
				</ActionText>

				<ActionButton
					v-if="folder.specialRole !== 'flagged'"
					icon="icon-mail"
					:title="t('mail', 'Mark all as read')"
					:disabled="loadingMarkAsRead"
					@click="markAsRead"
				>
					{{ t('mail', 'Mark all messages of this folder as read') }}
				</ActionButton>

				<ActionInput
					v-if="!account.isUnified && folder.specialRole !== 'flagged'"
					icon="icon-add"
					@submit="createFolder"
				>
					{{ t('mail', 'Add subfolder') }}
				</ActionInput>

				<ActionButton
					v-if="debug && !account.isUnified && folder.specialRole !== 'flagged'"
					icon="icon-settings"
					:title="t('mail', 'Clear cache')"
					:disabled="clearingCache"
					@click="clearCache"
				>
					{{ t('mail', 'Clear locally cached data, in case there are issues with synchronization.') }}
				</ActionButton>
			</template>
		</template>
		<AppNavigationCounter v-if="folder.unread" slot="counter">
			{{ folder.unread }}
		</AppNavigationCounter>

		<!-- subfolders -->
		<NavigationFolder
			v-for="subFolder in subFolders"
			:key="genId(subFolder)"
			:account="account"
			:folder="subFolder"
			:top="false"
		/>
	</AppNavigationItem>
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationCounter from '@nextcloud/vue/dist/Components/AppNavigationCounter'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'

import {clearCache} from '../service/MessageService'
import {getFolderStats} from '../service/FolderService'
import logger from '../logger'
import {translatePlural as n} from '@nextcloud/l10n'
import {translate as translateMailboxName} from '../i18n/MailboxTranslator'

export default {
	name: 'NavigationFolder',
	components: {
		AppNavigationItem,
		AppNavigationCounter,
		ActionText,
		ActionButton,
		ActionInput,
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
			folderStats: undefined,
			loadingMarkAsRead: false,
			clearingCache: false,
		}
	},
	computed: {
		visible() {
			return (
				this.account.showSubscribedOnly === false ||
				(this.folder.attributes && this.folder.attributes.includes('\\subscribed'))
			)
		},
		title() {
			if (this.filter === 'starred') {
				// Little hack to trick the translation logic into a different path
				return translateMailboxName({
					...this.folder,
					specialUse: ['flagged'],
				})
			}
			return translateMailboxName(this.folder)
		},
		folderId() {
			return atob(this.folder.id)
		},
		icon() {
			if (this.filter === 'starred') {
				return 'icon-flagged'
			} else if (this.folder.isPriorityInbox) {
				return 'icon-important'
			}
			return this.folder.specialRole ? 'icon-' + this.folder.specialRole : 'icon-folder'
		},
		to() {
			return {
				name: 'folder',
				params: {
					accountId: this.account.id,
					folderId: this.folder.id,
					filter: this.filter ? this.filter : undefined,
				},
			}
		},
		subFolders() {
			return this.$store.getters.getSubfolders(this.account.id, this.folder.id)
		},
		statsText() {
			if (this.folderStats && 'total' in this.folderStats && 'unread' in this.folderStats) {
				if (this.folderStats.unread === 0) {
					return n('mail', '{total} message', '{total} messages', this.folderStats.total, {
						total: this.folderStats.total,
					})
				} else {
					return n(
						'mail',
						'{unread} unread of {total}',
						'{unread} unread of {total}',
						this.folderStats.unread,
						{
							total: this.folderStats.total,
							unread: this.folderStats.unread,
						}
					)
				}
			}
			return t('mail', 'Loading …')
		},
	},
	methods: {
		/**
		 * Generate unique key id for a specific folder
		 */
		genId(folder) {
			return 'account-' + this.account.id + '_' + folder.id
		},

		/**
		 * On menu toggle, fetch stats
		 * @param {boolean} open menu opened state
		 */
		onMenuToggle(open) {
			if (open) {
				this.fetchFolderStats()
			}
		},

		/**
		 * Fetch folder unread/read stats
		 */
		async fetchFolderStats() {
			this.folderStats = null
			if (this.account.isUnified || this.folder.specialRole === 'flagged') {
				return
			}

			try {
				const stats = await getFolderStats(this.account.id, this.folder.id)
				logger.debug('loaded folder stats', {stats})
				this.folderStats = stats
			} catch (error) {
				this.folderStats = {error: true}
				logger.error(`could not load folder stats for ${this.folder.id}`, error)
			}
		},

		createFolder(e) {
			const name = e.target.elements[1].value
			const withPrefix = atob(this.folder.id) + this.folder.delimiter + name
			logger.info(`creating folder ${withPrefix} as subfolder of ${this.folder.id}`)
			this.menuOpen = false
			this.$store
				.dispatch('createFolder', {
					account: this.account,
					name: withPrefix,
				})
				.then(() => logger.info(`folder ${withPrefix} created`))
				.catch((error) => {
					logger.error(`could not create folder ${withPrefix}`, {error})
					throw error
				})
		},
		markAsRead() {
			this.loadingMarkAsRead = true

			this.$store
				.dispatch('markFolderRead', {
					accountId: this.account.id,
					folderId: this.folder.id,
				})
				.then(() => logger.info(`folder ${this.folder.id} marked as read`))
				.catch((error) => logger.error(`could not mark folder ${this.folder.id} as read`, {error}))
				.then(() => (this.loadingMarkAsRead = false))
		},
		async clearCache() {
			try {
				this.clearingCache = true
				logger.debug('clearing message cache', {
					accountId: this.account.id,
					folderId: this.folder.id,
				})

				await clearCache(this.account.id, this.folder.id)

				// TODO: there might be a nicer way to handle this
				window.location.reload(false)
			} finally {
				this.clearCache = false
			}
		},
	},
}
</script>
