<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog
		v-if="hasTextBlockes"
		id="text-block-picker"
		:name="t('mail', 'Text blocks')"
		:buttons="saveButtons"
		@closing="handleClose">
		<p>{{ t('mail', 'Choose a text block to insert at the cursor') }}</p>
		<ListItem
			v-for="textBlock in getMyTextBlocks()"
			:key="textBlock.id"
			:text-block="textBlock"
			:is-view-mode="true"
			:picked="textBlock.id === picked?.id"
			@click="handleClick" />
		<ListItem
			v-for="textBlock in getSharedTextBlocks()"
			:key="textBlock.id"
			:text-block="textBlock"
			:shared="true"
			:is-view-mode="true"
			:picked="textBlock.id === picked?.id"
			@click="handleClick" />
	</NcDialog>
	<NcDialog
		v-else
		id="text-block-picker"
		:name="t('mail', 'Text blocks')"
		:message="t('mail', 'Text blocks are reusable pieces of text that can be inserted in messages. Visit the Settings panel to create your own.')"
		@closing="handleClose">
	</NcDialog>
</template>

<script>
import IconCheck from '@mdi/svg/svg/check.svg'
import { NcDialog } from '@nextcloud/vue'
import { mapState } from 'pinia'
import ListItem from './ListItem.vue'
import useMainStore from '../../store/mainStore.js'

export default {
	name: 'TextBlockModal',
	components: {
		ListItem,
		NcDialog,
	},

	data() {
		return {
			picked: null,
		}
	},

	computed: {
		...mapState(useMainStore, ['getMyTextBlocks', 'getSharedTextBlocks']),

		hasTextBlockes() {
			return this.getMyTextBlocks().length > 0 || this.getSharedTextBlocks().length > 0
		},

		saveButtons() {
			return [
				{
					variant: 'primary',
					disabled: !this.picked,
					callback: () => this.$emit('insert', this.picked),
					label: t('mail', 'Insert'),
					icon: IconCheck,
				},
			]
		},
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
