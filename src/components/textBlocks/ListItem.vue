<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcListItem class="text-block-list-item" :active="picked" @click="handleListItemClick">
			<template #name>
				{{ textBlock.title }}
			</template>
			<template #subname>
				{{ textBlock.preview }}
			</template>
			<template v-if="shares.length > 0" #extra-actions>
				<AccountMultiple :title="t('mail', 'Shared')"
					:size="20" />
			</template>
			<template v-if="!shared" #actions>
				<NcActionButton @click="editModalOpen = true">
					<template #icon>
						<IconPencil :size="20" />
					</template>
					{{ t('mail','Edit {title}', { title: textBlock.title }) }}
				</NcActionButton>
				<NcActionButton v-if="!isViewMode" icon="icon-delete" @click="deleteTextBlock()">
					{{ t('mail','Delete {title}', { title: textBlock.title }) }}
				</NcActionButton>
			</template>
		</NcListItem>
		<NcDialog :open.sync="editModalOpen"
			:name="t('mail','Edit text block')"
			size="normal"
			:is-form="true">
			<p v-if="shared">
				{{ localTextBlock.title }}
			</p>
			<NcInputField v-else :value.sync="localTextBlock.title" :label="t('mail','Title of the text block')" />
			<TextEditor v-model="localTextBlock.content"
				:is-bordered="!shared"
				:html="true"
				:read-only="shared"
				:placeholder="t('mail','Content of the text block')"
				:bus="bus" />
			<template v-if="!shared">
				<h3>
					{{ t('mail','Shares') }}
				</h3>
				<NcSelect v-model="share"
					class="text-block-list-item__shares"
					:placeholder="t('mail','Search for users or groups')"
					:label-outside="true"
					:loading="loading"
					:user-select="true"
					:options="options"
					:get-option-label="option => option.displayName"
					@option:selecting="shareTextBlock"
					@search="asyncFind" />

				<NcListItem v-for="user in sortedShares"
					:key="user.shareWith"
					:name="user.displayName"
					:compact="true">
					<template #icon>
						<NcAvatar v-if="user.type === 'group'">
							<template #icon>
								<AccountMultiple :size="20" />
							</template>
						</NcAvatar>
						<NcAvatar v-else :user="user.shareWith" :display-name="user.displayName" />
					</template>
					<template #extra-actions>
						<NcButton type="tertiary-no-background" @click="removeShare(user)">
							<template #icon>
								<IconClose :size="20" />
							</template>
						</NcButton>
					</template>
				</NcListItem>
				<div class="text-block-buttons">
					<NcButton type="tertiary"
						class="text-block-buttons__button"
						:disabled="saveLoading"
						@click="closeTextBlockDialog">
						<template #icon>
							<IconClose :size="20" />
						</template>
						{{ t('mail', 'Cancel') }}
					</NcButton>
					<NcButton type="primary"
						class="text-block-buttons__button"
						:disabled="!localTextBlock.title || !localTextBlock.content || saveLoading"
						@click="saveTextBlock">
						<template #icon>
							<NcLoadingIcon v-if="saveLoading" :size="20" />
							<IconCheck v-else :size="20" />
						</template>
						{{ t('mail', 'Ok') }}
					</NcButton>
				</div>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import { NcActionButton, NcSelect, NcDialog, NcInputField, NcAvatar, NcListItem, NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import mitt from 'mitt'
import useMainStore from '../../store/mainStore.js'
import { getShares, shareTextBlock, unshareTextBlock } from '../../service/TextBlockService.js'
import TextEditor from '../TextEditor.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import AccountMultiple from 'vue-material-design-icons/AccountMultiple.vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconPencil from 'vue-material-design-icons/Pencil.vue'
import debounce from 'lodash/fp/debounce.js'
import { ShareType } from '@nextcloud/sharing'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import logger from '../../logger.js'

import axios from '@nextcloud/axios'

export default {
	name: 'ListItem',
	components: {
		NcActionButton,
		NcLoadingIcon,
		NcSelect,
		NcDialog,
		TextEditor,
		NcButton,
		NcAvatar,
		NcInputField,
		AccountMultiple,
		IconClose,
		IconPencil,
		IconCheck,
		NcListItem,
	},
	props: {
		textBlock: {
			type: Object,
			required: true,
		},
		shared: {
			type: Boolean,
			default: false,
		},
		isViewMode: {
			type: Boolean,
			default: false,
		},
		picked: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			shares: [],
			localTextBlock: JSON.parse(JSON.stringify(this.textBlock)),
			editModalOpen: false,
			loading: false,
			saveLoading: false,
			share: null,
			suggestions: [],
			bus: mitt(),
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
		if (!this.shared && !this.isViewMode) {
			this.shares = await getShares(this.textBlock.id)
		}
	},
	methods: {
		async deleteTextBlock() {
			await this.mainStore.deleteTextBlock({ id: this.textBlock.id }).then(() => {
				showSuccess(t('mail', 'Text block deleted'))
			}).catch(() => {
				showError(t('mail', 'Failed to delete text block'))
			})
		},
		async shareTextBlock(sharee) {
			try {
				await shareTextBlock(this.textBlock.id, sharee.shareWith, sharee.shareType === ShareType.User ? 'user' : 'group')
				this.shares.push({ shareWith: sharee.shareWith, type: sharee.isNoUser ? 'group' : 'user', displayName: sharee.displayName })
				showSuccess(t('mail', 'Text block shared with {sharee}', { sharee: sharee.shareWith }))
				this.share = null
			} catch (error) {
				showError(t('mail', 'Failed to share text block with {sharee}', { sharee: sharee.shareWith }))
			}
		},
		async removeShare(sharee) {
			try {
				await unshareTextBlock(this.textBlock.id, sharee.shareWith)
				this.shares = this.shares.filter(share => share.shareWith !== sharee.shareWith)
				showSuccess(t('mail', 'Share deleted for {name}', { name: sharee.shareWith }))
			} catch (error) {
				showError(t('mail', 'Failed to delete share with {name}', { name: sharee.shareWith }))
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
				logger.error('Error fetching suggestions', error)
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
			logger.info('suggestions', this.suggestions)
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
		handleListItemClick() {
			if (!this.isViewMode) {
				this.editModalOpen = true
				return
			}
			this.$emit('click', this.textBlock)
		},
		closeTextBlockDialog() {
			this.editModalOpen = false
			this.localTextBlock = JSON.parse(JSON.stringify(this.textBlock))
		},
		async saveTextBlock() {
			this.saveLoading = true
			try {
				await this.mainStore.patchTextBlock(this.localTextBlock)
				this.saveLoading = false
				this.editModalOpen = false
			} catch (error) {
				showError(t('mail', 'Failed to save text block'))
				logger.error('Failed to save text block', error)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.text-block-list-item {
	padding-inline-start: 0px !important;
	padding-inline-end: 0px !important;
	:deep(.list-item-content) {
		padding: 0px !important;
	}
}

.text-block-buttons {
	width: 100%;
	justify-self: end;
	display: flex;
	justify-content: flex-end;
	&__button {
		margin: var(--default-grid-baseline);
	}
}
</style>
