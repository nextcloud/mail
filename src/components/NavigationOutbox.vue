<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<AppNavigationItem
		id="navigation-outbox"
		key="navigation-outbox"
		:title="t('mail', 'Outbox')"
		:to="to">
		<template #icon>
			<IconOutbox
				class="outbox-opacity-icon"
				:size="20" />
		</template>
		<template #counter>
			<CounterBubble
				v-if="count"
				class="navigation-outbox__unread-counter">
				{{ count }}
			</CounterBubble>
		</template>
	</AppNavigationItem>
</template>

<script>
import { NcAppNavigationItem as AppNavigationItem, NcCounterBubble as CounterBubble } from '@nextcloud/vue'
import IconOutbox from 'vue-material-design-icons/InboxArrowUp'

export default {
	name: 'NavigationOutbox',
	components: {
		AppNavigationItem,
		CounterBubble,
		IconOutbox,
	},
	computed: {
		count() {
			return this.$store.getters['outbox/getAllMessages'].length
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
		margin-right: calc(var(--default-grid-baseline)*2);
	}
}

.outbox-opacity-icon {
	opacity: .7;
	&:hover {
		opacity: 1;
	}
}
</style>
