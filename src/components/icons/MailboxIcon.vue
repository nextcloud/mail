<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ImportantIcon
		v-if="mailbox.isPriorityInbox"
		:size="20" />
	<IconAllInboxes
		v-else-if="mailbox.id === UNIFIED_INBOX_ID && active"
		:size="20" />
	<IconAllInboxesOutline
		v-else-if="mailbox.id === UNIFIED_INBOX_ID"
		:size="20" />
	<IconInbox
		v-else-if="mailbox.specialRole === 'inbox' && !mailbox.isPriorityInbox && filter !== 'starred' && active"
		:size="20" />
	<IconInboxOutline
		v-else-if="mailbox.specialRole === 'inbox' && !mailbox.isPriorityInbox && filter !== 'starred'"
		:size="20" />
	<IconFavorite
		v-else-if="filter === 'starred' && active"
		:size="20" />
	<IconFavoriteOutline
		v-else-if="filter === 'starred'"
		:size="20" />
	<IconDraft
		v-else-if="mailbox.databaseId === account.draftsMailboxId && active"
		:size="20" />
	<IconDraftOutline
		v-else-if="mailbox.databaseId === account.draftsMailboxId"
		:size="20" />
	<IconSend
		v-else-if="mailbox.databaseId === account.sentMailboxId && active"
		:size="20" />
	<IconSendOutline
		v-else-if="mailbox.databaseId === account.sentMailboxId"
		:size="20" />
	<IconArchive
		v-else-if="mailbox.databaseId === account.archiveMailboxId && active"
		:size="20" />
	<IconArchiveOutline
		v-else-if="mailbox.databaseId === account.archiveMailboxId"
		:size="20" />
	<IconDelete
		v-else-if="mailbox.databaseId === account.trashMailboxId && active"
		:size="20" />
	<IconDeleteOutline
		v-else-if="mailbox.databaseId === account.trashMailboxId"
		:size="20" />
	<IconJunk
		v-else-if="mailbox.databaseId === account.junkMailboxId"
		:size="20" />
	<AlarmIcon
		v-else-if="mailbox.databaseId === account.snoozeMailboxId"
		:size="20" />
	<IconFolderShared
		v-else-if="mailbox.shared && active"
		:size="20" />
	<IconFolderSharedOutline
		v-else-if="mailbox.shared"
		:size="20" />
	<IconFolder
		v-else-if="active"
		:size="20" />
	<IconFolderOutline
		v-else
		:size="20" />
</template>

<script>

import AlarmIcon from 'vue-material-design-icons/Alarm.vue'
import IconArchive from 'vue-material-design-icons/ArchiveArrowDown.vue'
import IconArchiveOutline from 'vue-material-design-icons/ArchiveArrowDownOutline.vue'
import IconJunk from 'vue-material-design-icons/Fire.vue'
import IconFolder from 'vue-material-design-icons/Folder.vue'
import IconFolderShared from 'vue-material-design-icons/FolderAccount.vue'
import IconFolderSharedOutline from 'vue-material-design-icons/FolderAccountOutline.vue'
import IconFolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import IconInbox from 'vue-material-design-icons/Home.vue'
import IconInboxOutline from 'vue-material-design-icons/HomeOutline.vue'
import IconAllInboxes from 'vue-material-design-icons/InboxMultiple.vue'
import IconAllInboxesOutline from 'vue-material-design-icons/InboxMultipleOutline.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import IconDraft from 'vue-material-design-icons/Pencil.vue'
import IconDraftOutline from 'vue-material-design-icons/PencilOutline.vue'
import IconSend from 'vue-material-design-icons/Send.vue'
import IconSendOutline from 'vue-material-design-icons/SendOutline.vue'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import IconFavoriteOutline from 'vue-material-design-icons/StarOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCan.vue'
import IconDeleteOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import { UNIFIED_INBOX_ID } from '../../store/constants.js'

export default {
	name: 'MailboxIcon',
	components: {
		AlarmIcon,
		ImportantIcon,
		IconAllInboxes,
		IconAllInboxesOutline,
		IconInbox,
		IconInboxOutline,
		IconFavorite,
		IconFavoriteOutline,
		IconDraft,
		IconDraftOutline,
		IconSend,
		IconSendOutline,
		IconArchive,
		IconArchiveOutline,
		IconDelete,
		IconDeleteOutline,
		IconJunk,
		IconFolderShared,
		IconFolderSharedOutline,
		IconFolder,
		IconFolderOutline,
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

		active: {
			type: Boolean,
			default: false,
			required: false,
		},

		filter: {
			type: String,
			default: '',
			required: false,
		},
	},

	data() {
		return {
			UNIFIED_INBOX_ID,
		}
	},
}

</script>
