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
			size="large"
			:is-form="true"
			:buttons="buttons">
			<h2>{{ t('mail','Content') }}</h2>
			<NcInputField :value.sync="localSnippet.title" :label="t('mail','Title of the snippet')" />
			<TextEditor v-model="localSnippet.content"
				:html="true"
				:placeholder="t('mail','Content of the snippet')"
				:bus="bus" />
			<h2>{{ t('mail','Shares') }}</h2>
			<NcSelect v-if="!shared"
				v-model="share"
				class="snippet-list-item__shares"
				:loading="loading"
				:user-select="true"
				:options="options"
				:get-option-label="option => option.displayName"
				@option:selecting="shareSnippet"
				@search="asyncFind" />
			<template v-for="user in sortedShares">
				<NcUserBubble v-if="user.type === 'group'"
					:key="user.shareWith"
					avatar-image="icon-group"
					:display-name="user.shareWith">
					<template #name>
						<a href="#"
							title="Remove group"
							class="icon-close"
							@click="removeShare(user)" />
					</template>
				</NcUserBubble>
				<NcUserBubble v-else :key="user.shareWith" :user="user.shareWith">
					<template #name>
						<a href="#"
							title="Remove user"
							class="icon-close"
							@click="removeShare(user)" />
					</template>
				</NcUserBubble>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcSelect, NcDialog, NcInputField, NcUserBubble } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import useMainStore from '../../store/mainStore.js'
import { getShares, shareSnippet, unshareSnippet } from '../../service/SnippetService.js'
import TextEditor from '../TextEditor.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import IconCancel from '@mdi/svg/svg/cancel.svg'
import IconCheck from '@mdi/svg/svg/check.svg'
import debounce from 'lodash/fp/debounce.js'
import { ShareType } from '@nextcloud/sharing'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

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
		NcUserBubble,
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
			shares: [],
			localSnippet: Object.assign({}, this.snippet),
			editModalOpen: false,
			loading: false,
			share: null,
			suggestions: [],
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
					callback: () => { this.mainStore.patchSnippet(this.localSnippet) },
				},
			],
		}
	},
	computed: {
		...mapStores(useMainStore),
		options() {
			return this.suggestions.filter(suggestion => !this.shares.find(share => share.name === suggestion.shareWith) && suggestion.shareWith !== getCurrentUser().uid)
		},
		sortedShares() {
			return [...this.shares].sort((a, b) => {
				if (a.type === 'user' && b.type === 'group') {
					return -1
				}
				if (a.type === 'group' && b.type === 'user') {
					return 1
				}
				return 0
			})
		},
	},
	async mounted() {
		if (!this.shared) {
			this.shares = await getShares(this.snippet.id)
		}
	},
	methods: {
		async deleteSnippet() {
			await this.mainStore.deleteSnippet({ id: this.snippet.id }).then(() => {
				showSuccess(t('mail', 'Snippet deleted'))
			}).catch(() => {
				showError(t('mail', 'Failed to delete snippet'))
			})
		},
		async shareSnippet(sharee) {
			await shareSnippet(this.snippet.id, sharee.shareWith, sharee.shareType === ShareType.User ? 'user' : 'group').then(() => {
				this.shares.push({ shareWith: sharee.shareWith, type: sharee.isNoUser ? 'group' : 'user' })
				showSuccess(t('mail', 'Snippet shared with {sharee}', { sharee: sharee.shareWith }))
				this.share = null
			}).catch(() => {
				showError(t('mail', 'Failed to share snippet with {sharee}', { sharee: sharee.shareWith }))
			})
		},
		async removeShare(sharee) {
			await unshareSnippet(this.snippet.id, sharee.shareWith).then(() => {
				this.shares = this.shares.filter(share => share.shareWith !== sharee.shareWith)
				showSuccess(t('mail', 'Share deleted for {name}', { name: sharee.shareWith }))
			}).catch(() => {
				showError(t('mail', 'Failed to delete share with {name}', { name: sharee.shareWith }))
			})
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
			this.loading = true

			const shareTypes = [
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
						shareTypes,
					},
				})
			} catch (error) {
				console.error('Error fetching suggestions', error)
				return
			}

			const data = request.data.ocs.data
			const exact = request.data.ocs.data.exact
			data.exact = [] // removing exact from general results

			// flatten array of arrays
			const rawExactSuggestions = exact.users.concat(exact.groups)
			const rawSuggestions = data.users.concat(data.groups)
			// remove invalid data and format to user-select layout
			const exactSuggestions = rawExactSuggestions.map(share => this.formatForMultiselect(share))
			const suggestions = rawSuggestions.map(share => this.formatForMultiselect(share)).sort((a, b) => a.shareType - b.shareType)

			const allSuggestions = exactSuggestions.concat(suggestions).sort((a, b) => a.shareType - b.shareType)

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

			this.suggestions = allSuggestions.map(item => {
				// Make sure that items with duplicate displayName get the shareWith applied as a description
				if (nameCounts[item.displayName] > 1 && !item.desc) {
					return { ...item, desc: item.shareWithDisplayNameUnique }
				}
				return item
			})

			this.loading = false
			console.info('suggestions', this.suggestions)
		},
		/**
		 * Get the icon based on the share type
		 *
		 * @param {number} type the share type
		 * @return {string} the icon class
		 */
		 shareTypeToIcon(type) {
			switch (type) {
			case ShareType.User:
				// default is a user, other icons are here to differentiate
				// themselves from it, so let's not display the user icon
				// case this.SHARE_TYPES.SHARE_TYPE_REMOTE:
				// case this.SHARE_TYPES.SHARE_TYPE_USER:
				return {
					icon: 'icon-user',
					iconTitle: t('files_sharing', 'Guest'),
				}
			case ShareType.Group:
				return {
					icon: 'icon-group',
					iconTitle: t('files_sharing', 'Group'),
				}
			default:
				return {}
			}
		},

		/**
		 * Format shares for the multiselect options
		 *
		 * @param {object} result select entry item
		 * @return {object}
		 */
		formatForMultiselect(result) {
			return {
				shareWith: result.value.shareWith,
				shareType: result.value.shareType,
				user: result.uuid || result.value.shareWith,
				isNoUser: result.value.shareType !== ShareType.User,
				displayName: result.name || result.label,
				shareWithDisplayNameUnique: result.shareWithDisplayNameUnique || '',
				...this.shareTypeToIcon(result.value.shareType),
			}
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
	display: grid;
    grid-template-columns: 1fr 4fr 1fr;
    gap: 5px;
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
.icon-close {
	display: block;
	height: 100%;
}
</style>
