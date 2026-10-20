<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<DeleteTagModal
		v-if="deleteTagModal"
		:tag="tagToDelete"
		:envelopes="envelopes"
		:account-id="envelopes[0].accountId"
		@close="closeDeleteModal" />
	<NcModal
		v-else
		size="large"
		label-id="tag-modal-heading"
		@close="onClose">
		<div class="modal-content">
			<h2 id="tag-modal-heading" class="tag-title">
				{{ t('mail', 'Add default tags') }}
			</h2>
			<TagItem
				v-for="tag in tags"
				:key="tag.id"
				:tag="tag"
				:envelopes="envelopes"
				@delete-tag="deleteTag" />

			<h2 class="tag-title">
				{{ t('mail', 'Add tag') }}
			</h2>
			<div class="create-tag">
				<NcButton
					v-if="!editing"
					class="tagButton"
					@click="addTagInput">
					<template #icon>
						<IconAdd :size="20" />
					</template>
					{{ t('mail', 'Add tag') }}
				</NcButton>
				<NcActionInput v-if="editing" :disabled="showSaving" @submit="createTag">
					<template #icon>
						<IconTag :size="20" />
					</template>
				</NcActionInput>
				<NcActionText v-if="showSaving">
					<template #icon>
						<NcLoadingIcon :size="20" />
					</template>
					{{ t('mail', 'Saving tag …') }}
				</NcActionText>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { showError, showInfo } from '@nextcloud/dialogs'
import { NcActionInput, NcActionText, NcButton, NcLoadingIcon, NcModal } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconTag from 'vue-material-design-icons/TagOutline.vue'
import DeleteTagModal from './DeleteTagModal.vue'
import TagItem from './TagItem.vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { validateTag } from '../util/tag.js'
import { hiddenTags } from './tags.js'

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
		NcModal,
		NcColorPicker,
		NcTextField,
		DeleteTagModal,
		TagItem,
		NcButton,
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
			deleteTagModal: false,
			tagToDelete: null,
			color: randomColor(),
			newTagName: '',
			saving: false,
			errorMessage: '',
		}
	},

	computed: {
		...mapStores(useMainStore),
		heading() {
			if (this.envelopes.length === 1) {
				const subject = this.envelopes[0].subject || this.t('mail', 'No subject')
				// TRANSLATORS: {subject} is the subject of the selected email
				return this.t('mail', 'Assign tags to "{subject}"', { subject }, undefined, { escape: false })
			}
			return this.n('mail', 'Assign tags to %n message', 'Assign tags to %n messages', this.envelopes.length)
		},

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
				return a.displayName.localeCompare(b.displayName)
			})
		},
	},

	watch: {
		newTagName() {
			this.errorMessage = ''
		},
	},

	methods: {
		onClose() {
			this.$emit('close')
		},

		closeDeleteModal() {
			this.deleteTagModal = false
		},

		async createTag() {
			if (this.saving) {
				return
			}

			const displayName = this.newTagName.trim()
			const valid = validateTag(null, displayName, this.mainStore.getTags)
			if (valid !== true) {
				this.errorMessage = valid
				return
			}

			this.saving = true
			try {
				await this.mainStore.createTag({
					displayName,
					color: this.color,
				})
				this.newTagName = ''
				this.color = randomColor()
			} catch (error) {
				logger.error('could not create tag', { error })
				showError(this.t('mail', 'An error occurred, unable to create the tag.'))
			} finally {
				this.saving = false
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
