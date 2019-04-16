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
	<AppNavigationItem :item="data" />
</template>

<script>
import {AppNavigationItem} from 'nextcloud-vue'

import {translate as translateMailboxName} from '../l10n/MailboxTranslator'

export default {
	name: 'NavigationFolder',
	components: {
		AppNavigationItem,
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
	},
	computed: {
		data() {
			return this.folderToEntry(this.folder)
		},
	},
	methods: {
		folderToEntry(folder) {
			let icon = 'folder'
			if (folder.specialRole) {
				icon = folder.specialRole
			}

			return {
				id: 'account' + this.account.id + '_' + folder.id,
				key: 'account' + this.account.id + '_' + folder.id,
				text: translateMailboxName(folder),
				icon: 'icon-' + icon,
				router: {
					name: 'folder',
					params: {
						accountId: this.account.id,
						folderId: folder.id,
					},
					exact: false,
				},
				utils: {
					counter: folder.unread,
				},
				collapsible: true,
				opened: folder.opened,
				children: folder.folders.map(this.folderToEntry),
			}
		},
	},
}
</script>
