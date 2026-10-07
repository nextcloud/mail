<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="list-item-content__quick-actions">
		<NcButton
			v-if="isRead"
			variant="tertiary"
			:title="t('mail', 'Mark as unread')"
			@click.prevent="$emit('toggleSeen')">
			<template #icon>
				<EmailRead :size="20" />
			</template>
		</NcButton>
		<NcButton
			v-else
			variant="tertiary"
			:title="t('mail', 'Mark as read')"
			@click.prevent="$emit('toggleSeen')">
			<template #icon>
				<EmailUnread :size="20" />
			</template>
		</NcButton>
		<NcButton
			v-if="isImportant"
			variant="tertiary"
			:title="t('mail', 'Mark as unimportant')"
			@click.prevent="$emit('toggleImportant')">
			<template #icon>
				<ImportantIcon :size="20" />
			</template>
		</NcButton>
		<NcButton
			v-else
			variant="tertiary"
			:title="t('mail', 'Mark as important')"
			@click.prevent="$emit('toggleImportant')">
			<template #icon>
				<ImportantOutlineIcon :size="20" />
			</template>
		</NcButton>
		<NcButton
			variant="tertiary"
			:title="t('mail', 'Delete thread')"
			@click.prevent="$emit('delete')">
			<template #icon>
				<IconDelete :size="20" />
			</template>
		</NcButton>
	</div>
</template>

<script>

import NcButton from '@nextcloud/vue/components/NcButton'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import ImportantIcon from 'vue-material-design-icons/LabelVariant.vue'
import ImportantOutlineIcon from 'vue-material-design-icons/LabelVariantOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'

export default {
	name: 'EnvelopeSingleClickActions',
	components: {
		EmailRead,
		EmailUnread,
		ImportantIcon,
		ImportantOutlineIcon,
		IconDelete,
		NcButton,
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

	emits: ['toggleSeen', 'toggleImportant', 'delete'],
}
</script>

<style lang="scss" scoped>
.list-item-content__quick-actions {
	display: none;
}

.list-item:hover {
	.list-item-content__quick-actions {
		display: flex;
	}
}
</style>
