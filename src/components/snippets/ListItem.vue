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
			<TextEditor v-model="localSnippet.content"
				:html="true"
				:placeholder="t('mail','Content of the snippet')"
				:bus="bus"
				:show-toolbar="handleShowToolbar" />
			<NcSelect v-if="!shared"
				v-model="shares"
				:label="t('mail','Share with')"
				:multiple="true"
				class="snippet-list-item__shares"
				:loading="loading"
				:user-select="true"
				:options="options"
				@option:selecting="shareSnippet"
				@option:deselecting="removeShare"
				@search="asyncFind" />
		</NcDialog>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcSelect, NcDialog, NcInputField } from '@nextcloud/vue'
import { getShares, shareSnippet, unshareSnippet } from '../../service/SnippetService.js'
import TextEditor from '../TextEditor.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import IconCancel from '@mdi/svg/svg/cancel.svg'
import IconCheck from '@mdi/svg/svg/check.svg'
import debounce from 'lodash/fp/debounce.js'
import { ShareType } from '@nextcloud/sharing'
import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import mitt from 'mitt'

export default {
	name: 'ListItem',
	components: {
		NcActions,
		NcActionButton,
		NcSelect,
		NcDialog,
		TextEditor,
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
			loading: false,
			options: [],
			bus: mitt(),
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
			await unshareSnippet(this.snippet.id, sharee.displayName).then(() => {
				this.shares = this.shares.filter(share => share.displayName !== sharee.displayName)
				showSuccess(t('mail', 'Share deleted for {sharee}', { sharee }))
			}).catch(() => {
				showError(t('mail', 'Failed to delete share for {sharee}', { sharee }))
			})
		},
		/**
		 * Format shares for the multiselect options
		 *
		 * @param {object} result select entry item
		 * @return {object}
		 */
		 formatForSelect(result) {
			return {
				user: result.uuid || result.value.shareWith,
				displayName: result.name || result.label,
				subtitle: result.dsc | '',
			}
		},

		async asyncFind(query) {
			this.loading = true
			await this.debounceGetSuggestions(query.trim())
		},
		/**
		 * Get suggestions
		 *
		 * @param {string} search the search query
		 */
		 async getSuggestions(search) {
			const shareType = [
				ShareType.User,
				ShareType.Group,
			]
			let request = null
			try {
				request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
					params: {
						format: 'json',
						itemType: 'file',
						search,
						shareType,
					},
				})
			} catch (error) {
				console.error('Error fetching suggestions', error)
				return
			}
			const data = request.data.ocs.data
			const exact = request.data.ocs.data.exact
			data.exact = [] // removing exact from general results
			const rawExactSuggestions = exact.users
			const rawSuggestions = data.users
			console.info('rawExactSuggestions', rawExactSuggestions)
			console.info('rawSuggestions', rawSuggestions)
			// remove invalid data and format to user-select layout
			const exactSuggestions = rawExactSuggestions
				.map(share => this.formatForSelect(share))
			const suggestions = rawSuggestions
				.map(share => this.formatForSelect(share))
			const allSuggestions = exactSuggestions.concat(suggestions)
			// Count occurrences of display names in order to provide a distinguishable description if needed
			const nameCounts = allSuggestions.reduce((nameCounts, result) => {
				if (!result.displayName) {
					return nameCounts
				}
				if (!nameCounts[result.displayName]) {
					nameCounts[result.displayName] = 0
				}
				nameCounts[result.displayName]++
				return nameCounts
			}, {})
			this.options = allSuggestions.map(item => {
				// Make sure that items with duplicate displayName get the shareWith applied as a description
				if (nameCounts[item.displayName] > 1 && !item.desc) {
					return { ...item, desc: item.shareWithDisplayNameUnique }
				}
				return item
			})
			this.loading = false
			console.info('suggestions', this.options)
		},
		/**
		 * Debounce getSuggestions
		 *
		 * @param {...*} args the arguments
		 */
		 debounceGetSuggestions: debounce(300, function(...args) {
			this.getSuggestions(...args)
		}),
	},
}
</script>

<style lang="scss" scoped>
.snippet-list-item{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
	&__title{
		white-space: nowrap;
		padding-inline-end: 30px;
		width: 100px;
		text-overflow: ellipsis;
		overflow: hidden;
	}
	&__preview{
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	&__shares{
		width: 100%;
	}
}
</style>
