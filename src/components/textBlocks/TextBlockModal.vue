<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog id="text-block-picker" :name="t('mail','Text blocks')" @closing="handleClose">
		<p>{{ t('mail','Choose a text block to insert at the cursor') }}</p>
		<ListItem v-for="textBlock in getMyTextBlocks()"
			:key="textBlock.id"
			:text-block="textBlock"
			:is-view-mode="true"
			:picked="textBlock.id === picked?.id"
			@click="handleClick" />
		<ListItem v-for="textBlock in getSharedTextBlocks()"
			:key="textBlock.id"
			:text-block="textBlock"
			:shared="true"
			:is-view-mode="true"
			:picked="textBlock.id === picked?.id"
			@click="handleClick" />
		<NcButton class="insert-button"
			:disabled="!picked"
			@click="$emit('insert', picked)">
			{{ t('mail', 'Insert') }}
			<template #icon>
				<IconCheck :size="20" :name="t('mail','Insert text block')" />
			</template>
		</NcButton>
	</NcDialog>
</template>

<script>
import ListItem from './ListItem.vue'
import { NcDialog, NcButton } from '@nextcloud/vue'
import { mapState } from 'pinia'
import IconCheck from 'vue-material-design-icons/Check.vue'
import useMainStore from '../../store/mainStore.js'
export default {
	name: 'TextBlockModal',
	components: {
		ListItem,
		NcDialog,
		NcButton,
		IconCheck,
	},
	data() {
		return {
			picked: null,
		}
	},
	computed: {
		...mapState(useMainStore, ['getMyTextBlocks', 'getSharedTextBlocks']),
	},
	methods: {
		handleClick(textBlock) {
			this.picked = textBlock

		},
		handleClose() {
			this.$emit('close')
		},
	},
}
</script>

<style lang="scss" scoped>
.insert-button {
	justify-self: end;
	margin-bottom: calc( var(--default-grid-baseline) * 2) ;
}
</style>
