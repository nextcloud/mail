<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<DeleteTagModal
		v-if="deleteTagModal"
		:tag="tagToDelete"
		:envelopes="envelopes"
		:accountId="envelopes[0].accountId"
		@close="closeDeleteModal" />
	<NcModal
		v-else
		closeOnClickOutside
		class="tag-modal"
		size="large"
		labelId="tag-modal-heading"
		@close="onClose">
		<div class="modal-content">
			<div class="tag-list">
				<h2 id="tag-modal-heading" class="tag-title">
					{{ heading }}
				</h2>
				<TagItem
					v-for="tag in tags"
					:key="tag.id"
					:tag="tag"
					:envelopes="envelopes"
					@deleteTag="deleteTag" />
			</div>

			<div class="create-tag">
				<NcColorPicker v-model="color">
					<NcButton variant="tertiary" :aria-label="t('mail', 'Change tag color')">
						<template #icon>
							<div class="create-tag__color" :style="{ backgroundColor: color }" />
						</template>
					</NcButton>
				</NcColorPicker>
				<NcTextField
					v-model="newTagName"
					class="create-tag__name"
					:label="t('mail', 'Add tag')"
					:disabled="saving"
					:error="errorMessage !== ''"
					:helperText="errorMessage"
					showTrailingButton
					trailingButtonIcon="arrowEnd"
					:trailingButtonLabel="t('mail', 'Add tag')"
					@keyup.enter="createTag"
					@trailingButtonClick="createTag" />
			</div>
		</div>
	</NcModal>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcTextField from '@nextcloud/vue/components/NcTextField'
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

	emits: ['close'],

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
	display: flex;
	flex-direction: column;
	height: calc(100vh - 210px);
	min-width: min(480px, 90vw);
	padding: 0;
}

:deep(.modal-container) {
	min-width: min(480px, 90vw);
}

.tag-list {
	flex: 1;
	min-height: 0;
	overflow-y: auto;
	padding: calc(var(--default-grid-baseline) * 5);
	padding-bottom: calc(var(--default-grid-baseline) * 3);
}

.create-tag {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);
	flex-shrink: 0;
	border-top: 1px solid var(--color-border);
	padding: calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 5);
}

.create-tag__color {
	width: calc(var(--default-grid-baseline) * 5);
	height: calc(var(--default-grid-baseline) * 5);
	border-radius: 50%;
}

.create-tag__name {
	flex: 1;
}

.tag-title {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	margin-top: 0;
	margin-bottom: calc(var(--default-grid-baseline) * 2);
	margin-inline-start: var(--default-grid-baseline);
}

@media only screen and (max-width: 512px) {
	:deep(.modal-container) {
		top: 100px !important;
		max-height: calc(100vh - 170px) !important;
	}
}
</style>
