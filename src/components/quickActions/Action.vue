<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="action">
		<div class="action__info">
			<DragIcon class="action__info__drag" :size="16" />
			<Icon class="action__info__icon" :action="action.name" />
			<p v-if="!needsSelection">
				{{ actionTitle }}
			</p>
			<NcSelect v-else
				:input-label="actionTitle"
				:options="options"
				label="value"
				:model-value="selectedOption"
				@update:modelValue="update" />
		</div>
		<NcButton :aria-label="t('mail', 'delete')" variant="tertiary-no-background" @click="$emit('delete')">
			<template #icon>
				<CloseIcon :size="20" />
			</template>
		</NcButton>
	</div>
</template>

<script>
import useMainStore from '../../store/mainStore.js'
import { NcSelect, NcButton } from '@nextcloud/vue'
import { hiddenTags } from '../tags.js'
import Icon from './Icon.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import DragIcon from 'vue-material-design-icons/Drag.vue'

export default {
	name: 'Action',
	components: {
		NcSelect,
		CloseIcon,
		NcButton,
		Icon,
		DragIcon,
	},
	props: {
		action: {
			type: Object,
			required: true,
		},
		account: {
			type: Object,
			required: true,
		},
	},
	computed: {
		mainStore() {
			return useMainStore()
		},
		needsSelection() {
			return ['applyTag', 'moveThread'].includes(this.action.name)
		},
		selectedOption() {
			if (this.action.name === 'applyTag') {
				return this.options.find(option => option.id === this.action.tagId) || null
			} else if (this.action.name === 'moveThread') {
				return this.options.find(option => option.id === this.action.mailboxId) || null
			}
			return null
		},
		options() {
			if (this.action.name === 'applyTag') {
				return this.mainStore.getTags.filter((tag) => tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags)).map((tag) => ({
					value: tag.displayName,
					id: tag.id,
				}))
			}
			if (this.action.name === 'moveThread') {
				return this.mainStore.getMailboxes(this.account.accountId).map((mailbox) => ({
					value: mailbox.displayName,
					id: mailbox.databaseId,
				}))
			}
			return []
		},
		actionTitle() {
			switch (this.action.name) {
			case 'markAsSpam':
				return this.t('mail', 'Mark as spam')
			case 'applyTag':
				return this.t('mail', 'Tag')
			case 'moveThread':
				return this.t('mail', 'Move thread')
			case 'deleteThread':
				return this.t('mail', 'Delete thread')
			case 'markAsRead':
				return this.t('mail', 'Mark as read')
			case 'markAsUnread':
				return this.t('mail', 'Mark as unread')
			case 'markAsImportant':
				return this.t('mail', 'Mark as important')
			case 'markAsFavorite':
				return this.t('mail', 'Mark as favorite')
			default:
				return this.action.name
			}
		},
	},
	methods: {
		update(value) {
			this.$emit('update', { id: value.id, type: this.action.name })
		},
	},
}
</script>
<style lang="scss" scoped>
.action {
	display: flex;
	width: 100%;
	justify-content: space-between;
	align-items: center;
	&__info{
		display: flex;
		&__icon{
			margin-inline-end : 3px
		}
		&__drag{
			margin-inline-end : 6px;
			cursor: grab;
		}
	}
}
</style>
