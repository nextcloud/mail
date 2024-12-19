<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<ListItem v-for="snippet in snippets" :key="snippet.id" :snippet="snippet" />
		<span v-if="!snippets.length"> {{ t('mail', 'No snippets available') }} </span>
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
		snippets: {
			type: Array,
			required: true,
		},
	},
	methods: {
	},
}
</script>

<style lang="scss" scoped>
</style>
