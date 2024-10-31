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
			{{ snippet.content }}
		</p>

		<NcSelect v-if="!shared"
			:label="t('mail','Share with')"
			:multiple="true"
			class="snippet-list-item__shares"
			:options="['hamzamahjoubi', 'user1', 'user2']"
			@option:selecting="shareSnippet"
			@option:deselecting="removeShare" />

		<NcActions class="snippet-list-item__actions">
			<NcActionButton icon="icon-delete" @click="deleteSnippet()">
				{{ t('mail','Delete {title}', { title: snippet.title }) }}
			</NcActionButton>
			<NcActionButton icon="icon-edit" @click="editModalOpen = true">
				{{ t('mail','Edit {title}', { title: snippet.title }) }}
			</NcActionButton>
		</NcActions>
		<NcDialog :open.sync="editModalOpen"
			:name="t('mail','Edit snippet')"
			:is-form="true"
			:buttons="buttons"
			size="normal">
			<NcInputField :value.sync="localSnippet.title" :label="t('mail','Title of the snippet')" />
			<NcTextArea rows="7"
				:value.sync="localSnippet.content"
				:label="t('mail','Content of the snippet')"
				resize="horizontal" />
		</NcDialog>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcSelect, NcDialog, NcTextArea, NcInputField } from '@nextcloud/vue'
import { getShares, shareSnippet, unshareSnippet } from '../../service/SnippetService.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
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
					callback: () => {
						this.editModalOpen = false
						this.localSnippet = Object.assign({}, this.snippet)
					},
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
			this.shares = await getShares(this.snippet.id).then((response) => {
				return response.map(share => share.shareWith)
			})
		}
	},
	methods: {
		async deleteSnippet() {
			await this.$store.dispatch('deleteSnippet', { id: this.snippet.id }).then(() => {
				showSuccess(t('mail', 'Snippet deleted'))
			}).catch(() => {
				showError(t('mail', 'Failed to delete snippet'))
			})
		},
		async shareSnippet(sharee) {
			await shareSnippet(this.snippet.id, sharee.displayName, sharee.type).then(() => {
				this.shares.push(sharee.displayName)
				showSuccess(t('mail', 'Snippet shared with {sharee}', { sharee: sharee.displayName }))
			}).catch(() => {
				showError(t('mail', 'Failed to share snippet with {sharee}', { sharee: sharee.displayName }))
			})
		},
		async removeShare(sharee) {
			await unshareSnippet(this.snippet.id, sharee).then(() => {
				this.shares = this.shares.filter(share => share !== sharee)
				showSuccess(t('mail', 'Share deleted for {sharee}', { sharee }))
			}).catch(() => {
				showError(t('mail', 'Failed to delete share for {sharee}', { sharee }))
			})
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
	&__preview{
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
}
</style>
