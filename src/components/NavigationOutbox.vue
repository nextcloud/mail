<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppNavigationItem id="navigation-outbox"
		key="navigation-outbox"
		:name="t('mail', 'Outbox')"
		:to="to">
		<template #icon>
			<IconOutbox class="outbox-opacity-icon"
				:size="20" />
		</template>
		<template #counter>
			<CounterBubble v-if="count"
				class="navigation-outbox__unread-counter">
				{{ count }}
			</CounterBubble>
		</template>
	</AppNavigationItem>
</template>

<script>
import { NcAppNavigationItem as AppNavigationItem, NcCounterBubble as CounterBubble } from '@nextcloud/vue'
import IconOutbox from 'vue-material-design-icons/InboxArrowUp.vue'

import useOutboxStore from '../store/outboxStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'NavigationOutbox',
	components: {
		AppNavigationItem,
		CounterBubble,
		IconOutbox,
	},
	computed: {
		...mapStores(useOutboxStore),
		count() {
			return this.outboxStore.getAllMessages.length
		},
		to() {
			return {
				name: 'outbox',
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.navigation-outbox {
	&__unread-counter {
		margin-inline-end: calc(var(--default-grid-baseline) * 2);
	}
}

.outbox-opacity-icon {
	opacity: .7;
	&:hover {
		opacity: 1;
	}
}
</style>
