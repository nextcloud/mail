<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<ListItem v-for="textBlock in textBlocks"
			:key="textBlock.id"
			:text-block="textBlock"
			:shared="shared" />
		<span v-if="!textBlocks.length"> {{ t('mail', 'No text blocks available') }} </span>
	</div>
</template>

<script>
import ListItem from './ListItem.vue'
export default {
	name: 'List',
	components: {
		ListItem,
	},
	props: {
		shared: {
			type: Boolean,
			default: false,
		},
		textBlocks: {
			type: Array,
			required: true,
		},
	},
	methods: {
	},
}
</script>
