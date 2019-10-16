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
		:id="genId(folder)"
		:key="genId(folder)"
		:allow-collapse="true"
		:icon="icon"
		:title="title"
		:to="to"
		@update:menuOpen="onMenuToggle"
	>
		<!-- actions -->
		<template slot="actions">
			<template v-if="top">
				<ActionText icon="icon-info" :title="folderId">
					{{ statsText }}
				</ActionText>

				<!-- TODO: make *mark as read* available for all folders once there is more than one action -->
				<ActionButton icon="icon-checkmark" :title="t('mail', 'Mark all as read')" @click="markAsRead">
					{{ t('mail', 'Mark all messages of this folder as read') }}
				</ActionButton>

				<ActionInput icon="icon-add" @submit="createFolder">
					{{ t('mail', 'Add subfolder') }}
				</ActionInput>
			</template>
		</template>
		<AppNavigationCounter slot="counter">
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
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'

import {getFolderStats} from '../service/FolderService'
import Logger from '../logger'
import {translate as translateMailboxName} from '../l10n/MailboxTranslator'

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
	},
	data() {
		return {
			folderStats: undefined,
		}
	},
	computed: {
		title() {
			return translateMailboxName(this.folder)
		},
		folderId() {
			return atob(this.folder.id)
		},
		icon() {
			return this.folder.specialRole ? 'icon-' + this.folder.specialRole : 'icon-folder'
		},
		to() {
			return {
				name: 'folder',
				params: {
					accountId: this.account.id,
					folderId: this.folder.id,
				},
			}
		},
		subFolders() {
			return this.$store.getters.getSubfolders(this.account.id, this.folder.id)
		},
		statsText() {
			if (this.folderStats && 'total' in this.folderStats && 'unread' in this.folderStats) {
				return t('mail', '{total} messages ({unread} unread)', {
					total: this.folderStats.total,
					unread: this.folderStats.unread,
				})
			}
			return t('mail', 'Loading message stats')
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
			try {
				const stats = await getFolderStats(this.account.id, this.folder.id)
				Logger.debug('loaded folder stats', {stats})
				this.folderStats = stats
			} catch (error) {
				this.folderStats = {error: true}
				Logger.error(`could not load folder stats for ${this.folder.id}`, error)
			}
		},

		createFolder(e) {
			const name = e.target.elements[0].value
			const withPrefix = atob(this.folder.id) + this.folder.delimiter + name
			Logger.info(`creating folder ${withPrefix} as subfolder of ${this.folder.id}`)
			this.menuOpen = false
			this.$store
				.dispatch('createFolder', {account: this.account, name: withPrefix})
				.then(() => Logger.info(`folder ${withPrefix} created`))
				.catch(error => {
					Logger.error(`could not create folder ${withPrefix}`, {error})
					throw error
				})
		},
		markAsRead() {
			return () => {
				this.menuOpen = false
				this.$store
					.dispatch('markFolderRead', {account: this.account, folderId: this.folder.id})
					.then(() => Logger.info(`folder ${this.folder.id} marked as read`))
					.catch(error => Logger.error(`could not mark folder ${this.folder.id} as read`, {error}))
			}
		},
	},
}
</script>
