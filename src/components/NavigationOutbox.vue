<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
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
			<CounterBubble v-if="count">
				{{ count }}
			</CounterBubble>
		</template>
	</AppNavigationItem>
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import CounterBubble from '@nextcloud/vue/dist/Components/CounterBubble'
import IconOutbox from 'vue-material-design-icons/Email'

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
::v-deep .counter-bubble__counter {
	margin-right: 43px;
}
.outbox-opacity-icon {
	opacity: .7;

	&:hover {
		opacity: 1;
	}
}
</style>
