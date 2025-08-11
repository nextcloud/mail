<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="list-item-content__quick-actions">
		<EmailUnread v-if="isRead"
			:size="18"
			:title="t('mail', 'Mark as unread')"
			@click.prevent="$emit('toggle-seen')" />
		<EmailRead v-else
			:size="18"
			:title="t('mail', 'Mark as read')"
			@click.prevent="$emit('toggle-seen')" />
		<ImportantIcon v-if="isImportant"
			:size="18"
			:title="t('mail', 'Mark as unimportant')"
			@click.prevent="$emit('toggle-important')" />
		<ImportantOutlineIcon v-else
			:size="18"
			:title="t('mail', 'Mark as important')"
			@click.prevent="$emit('toggle-important')" />
		<IconDelete :size="18"
			:title="t('mail', 'Delete thread')"
			@click.prevent="$emit('delete')" />
	</div>
</template>

<script>

import ImportantOutlineIcon from 'vue-material-design-icons/LabelVariantOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import IconDelete from 'vue-material-design-icons/DeleteOutline.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'

export default {
	name: 'EnvelopeSingleClickActions',
	components: {
		EmailRead,
		EmailUnread,
		ImportantIcon,
		ImportantOutlineIcon,
		IconDelete,
	},
	props: {
		isRead: {
			type: Boolean,
			default: false,
		},
		isImportant: {
			type: Boolean,
			default: false,
		},
	},
}
</script>

<style lang="scss" scoped>
.list-item-content__quick-actions {
	display: none;
}

.list-item:hover {
	.list-item-content__quick-actions {
		display: flex;
		gap: calc(var(--default-grid-baseline) * 2);
		padding-inline-start: calc(var(--default-grid-baseline) * 2);

		:deep(svg) {
			fill: var(--color-main-text);

			&:hover {
				opacity: 0.5;
			}
		}
	}
}
</style>
