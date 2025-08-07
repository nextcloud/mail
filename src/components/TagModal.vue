<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<DeleteTagModal v-if="deleteTagModal"
		:tag="tagToDelete"
		:envelopes="envelopes"
		:account-id="envelopes[0].accountId"
		@close="closeDeleteModal" />
	<Modal v-else size="large" @close="onClose">
		<div class="modal-content">
			<h2 class="tag-title">
				{{ t('mail', 'Add default tags') }}
			</h2>
			<TagItem v-for="tag in tags"
				:key="tag.id"
				:tag="tag"
				:envelopes="envelopes"
				@delete-tag="deleteTag" />

			<h2 class="tag-title">
				{{ t('mail', 'Add tag') }}
			</h2>
			<div class="create-tag">
				<NcButton v-if="!editing"
					class="tagButton"
					@click="addTagInput">
					<template #icon>
						<IconAdd :size="20" />
					</template>
					{{ t('mail', 'Add tag') }}
				</NcButton>
				<ActionInput v-if="editing" :disabled="showSaving" @submit="createTag">
					<template #icon>
						<IconTag :size="20" />
					</template>
				</ActionInput>
				<ActionText v-if="showSaving">
					<template #icon>
						<IconLoading :size="20" />
					</template>
					{{ t('mail', 'Saving tag …') }}
				</ActionText>
			</div>
		</div>
	</Modal>
</template>

<script>
import { NcModal as Modal, NcActionText as ActionText, NcActionInput as ActionInput, NcLoadingIcon as IconLoading, NcButton } from '@nextcloud/vue'
import DeleteTagModal from './DeleteTagModal.vue'
import TagItem from './TagItem.vue'
import IconTag from 'vue-material-design-icons/TagOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import { showError, showInfo } from '@nextcloud/dialogs'
import { hiddenTags } from './tags.js'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

function randomColor() {
	let randomHexColor = ((1 << 24) * Math.random() | 0).toString(16)
	while (randomHexColor.length < 6) {
		randomHexColor = '0' + randomHexColor
	}
	return '#' + randomHexColor
}
export default {
	name: 'TagModal',
	components: {
		Modal,
		ActionText,
		ActionInput,
		DeleteTagModal,
		IconTag,
		IconLoading,
		TagItem,
		NcButton,
		IconAdd,
	},
	props: {
		envelopes: {
			// The envelopes on which this menu will act
			required: true,
			type: Array,
		},
	},
	data() {
		return {
			isAdded: false,
			editing: false,
			tagLabel: true,
			tagInput: false,
			showSaving: false,
			renameTagLabel: true,
			renameTagInput: false,
			deleteTagModal: false,
			tagToDelete: null,
			color: randomColor(),
			editColor: '',
		}
	},
	computed: {
		...mapStores(useMainStore),
		tags() {
			return this.mainStore.getTags.filter((tag) => tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags)).sort((a, b) => {
				if (a.isDefaultTag && !b.isDefaultTag) {
					return -1
				}
				if (b.isDefaultTag && !a.isDefaultTag) {
					return 1
				}
				if (a.isDefaultTag && b.isDefaultTag) {
					if (a.displayName < b.displayName) {
						return 1
					}
					return -1
				}
				if (this.isSet(a.imapLabel) && !this.isSet(b.imapLabel)) {
					return -1
				}
				if (!this.isSet(a.imapLabel) && this.isSet(b.imapLabel)) {
					return 1
				}
				return a.displayName.localeCompare(b.displayName)
			})
		},
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		closeDeleteModal() {
			this.deleteTagModal = false
		},
		isSet(imapLabel) {
			return this.envelopes.some(
				(envelope) => (
					this.mainStore.getEnvelopeTags(envelope.databaseId).some(
						tag => tag.imapLabel === imapLabel,
					)
				),
			)
		},
		addTagInput() {
			this.editing = true
			this.showSaving = false
		},
		async createTag(event) {
			this.editing = true
			if (this.showSaving) {
				return
			}

			const displayName = event.target.querySelector('input[type=text]').value
			if (displayName.toLowerCase() in hiddenTags) {
				showError(this.t('mail', 'Tag name is a hidden system tag'))
				return
			}
			if (this.mainStore.getTags.some(tag => tag.displayName === displayName)) {
				showError(this.t('mail', 'Tag already exists'))
				return
			}
			if (displayName.trim() === '') {
				showError(this.t('mail', 'Tag name cannot be empty'))
				return
			}
			try {
				await this.mainStore.createTag({
					displayName,
					color: randomColor(displayName),
				})
			} catch (error) {
				console.debug(error)
				showError(this.t('mail', 'An error occurred, unable to create the tag.'))
			} finally {
				this.showSaving = false
				this.tagLabel = true
			}
		},
		convertHex(color, opacity) {
			if (color.length === 4) {
				const r = parseInt(color.substring(1, 2), 16)
				const g = parseInt(color.substring(2, 3), 16)
				const b = parseInt(color.substring(3, 4), 16)
				return `rgba(${r}, ${g}, ${b}, ${opacity})`
			} else {
				const r = parseInt(color.substring(1, 3), 16)
				const g = parseInt(color.substring(3, 5), 16)
				const b = parseInt(color.substring(5, 7), 16)
				return `rgba(${r}, ${g}, ${b}, ${opacity})`
			}
		},
		openEditTag() {
			this.renameTagLabel = false
			this.renameTagInput = true
			this.showSaving = false

		},
		async renameTag(tag, event) {
			this.renameTagInput = false
			this.showSaving = false
			const displayName = event.target.querySelector('input[type=text]').value

			try {
				await this.mainStore.updateTag({
					tag,
					displayName,
					color: tag.color,
				})
				this.renameTagLabel = true
				this.renameTagInput = false
				this.showSaving = false
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the tag.'))
				console.error(error)
				this.renameTagLabel = false
				this.renameTagInput = false
				this.showSaving = true
			}
		},
		deleteTag(tag) {
			this.tagToDelete = tag
			this.deleteTagModal = true
		},

	},
}

</script>

<style lang="scss" scoped>
:deep(.modal-content) {
	padding: 20px 20px 20px 20px;
	max-height: calc(100vh - 210px);
	overflow-y: auto;
}

:deep(.modal-container) {
	width: auto !important;
}

.icon-colorpicker {
	background-image: var(--icon-add-fff);
}

.tagButton {
	display: inline-block;
	margin-inline-start: 10px;
}

.tag-title {
	margin-top: 20px;
	margin-inline-start: 10px;
}

.create-tag {
	list-style: none;
	margin-bottom:12px;
}
@media only screen and (max-width: 512px) {
	:deep(.modal-container) {
	top: 100px !important;
	max-height: calc(100vh - 170px) !important
	}
}
</style>
