<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="snippet-list-item">
		<p class="snippet-list-item__title">
			{{ snippet.title }}
		</p>
		<p class="snippet-list-item__preview">
			{{ snippet.preview }}
		</p>

		<NcSelect v-if="!shared"
			class="snippet-list-item__shares"
			:options="['hamzamahjoubi', 'user1', 'user2']"
			@change="shareSnippet(snippet.id, snippet.sharedWith)" />

		<NcActions class="snippet-list-item__actions">
			<NcActionButton icon="icon-delete" @click="deleteSnippet(snippet.id)">
				{{ t('mail','Delete {title}', { title: snippet.title }) }}
			</NcActionButton>
			<NcActionButton icon="icon-edit" @click="editSnippet(snippet.id)">
				{{ t('mail','Edit {title}', { title: snippet.title }) }}
			</NcActionButton>
		</NcActions>
		<NcDialog :open.sync="editModalOpen"
			:name="t('mail','Edit snippet')"
			:is-form="true"
			:buttons="buttons"
			size="normal">
			<NcInputField :value="snippet.title" :label="t('mail','Title of the snippet')" />
			<NcTextArea rows="7" :label="t('mail','Content of the snippet')" resize="horizontal" />
		</NcDialog>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcSelect, NcDialog, NcTextArea, NcInputField } from '@nextcloud/vue'
import { getShares } from '../../service/SnippetService.js'
import IconCancel from '@mdi/svg/svg/cancel.svg?raw'
import IconCheck from '@mdi/svg/svg/check.svg?raw'

export default {
	name: 'ListItem',
	components: {
		NcActions,
		NcActionButton,
		NcSelect,
		NcDialog,
		NcTextArea,
		NcInputField,
	},
	props: {
		snippet: {
			type: Object,
			required: true,
		},
		shared: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			shares: null,
			localSnippet: Object.assign({}, this.snippet),
			editModalOpen: false,
			buttons: [
				{
					label: 'Cancel',
					icon: IconCancel,
					callback: () => { console.log('Pressed "Cancel"') },
				},
				{
					label: 'Ok',
					type: 'primary',
					icon: IconCheck,
					callback: () => { this.$store.dispatch('patchSnippet', this.localSnippet) },
				},
			],
		}
	},
	async mounted() {
		if (!this.shared) {
			this.shares = await getShares()
		}
	},
	updated() {
	},
	methods: {
		deleteSnippet(id) {
			console.log('deleteSnippet', id)
		},
		shareSnippet(id, sharee) {
			console.log('shareSnippet', id, sharee)
		},
		editSnippet(id) {
			this.editModalOpen = true
			console.log('editSnippet', id)
		},
	},
}
</script>

<style lang="scss" scoped>
.snippet-list-item{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
}
</style>
