<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<component :is="quickActionIcon" :size="20" />
</template>

<script>
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconEmailFast from 'vue-material-design-icons/EmailFastOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import TagIcon from 'vue-material-design-icons/TagOutline.vue'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
export default {
	name: 'Icon',
	props: {
		action: {
			type: String,
			default: '',
		},
	},
	computed: {
		quickActionIcon() {
			switch (this.action) {
			case 'markAsSpam':
				return AlertOctagonIcon
			case 'applyTag':
				return TagIcon
			case 'markAsImportant':
				return ImportantIcon
			case 'markAsFavorite':
				return IconFavorite
			case 'markAsRead':
				return EmailRead
			case 'markAsUnread':
				return EmailUnread
			case 'moveThread':
				return OpenInNewIcon
			case 'deleteThread':
				return IconDelete
			default:
				return IconEmailFast
			}
		},
	},
}
</script>
