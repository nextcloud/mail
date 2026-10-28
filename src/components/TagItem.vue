<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="tag-group">
		<template v-if="editing">
			<NcColorPicker v-model="editColor" @submit="updateColor">
				<NcButton variant="tertiary" :aria-label="t('mail', 'Change tag color')">
					<template #icon>
						<div class="tag-group__color" :style="{ backgroundColor: editColor }" />
					</template>
				</NcButton>
			</NcColorPicker>
			<NcTextField
				v-model="currentTagName"
				class="tag-group__name"
				:label="t('mail', 'Tag name')"
				:disabled="saving"
				:error="hasError()"
				:helperText="errorMessage"
				showTrailingButton
				trailingButtonIcon="arrowEnd"
				:trailingButtonLabel="t('mail', 'Save')"
				@keyup.enter="renameTag(tag)"
				@trailingButtonClick="renameTag(tag)" />
			<NcButton variant="tertiary" :aria-label="t('mail', 'Cancel')" @click="closeEditTag">
				<template #icon>
					<IconClose :size="20" />
				</template>
			</NcButton>
		</template>
		<template v-else>
			<NcCheckboxRadioSwitch
				class="tag-group__checkbox"
				:modelValue="isSet(tag.imapLabel)"
				@update:modelValue="$event ? addTag(tag.imapLabel) : removeTag(tag.imapLabel)">
				<span
					class="tag-group__label"
					:style="{
						color: tagTextColor(tag.color, isDarkTheme),
						'background-color': tagBackgroundColor(tag.color),
					}">
					{{ translateTagDisplayName(tag) }}
				</span>
			</NcCheckboxRadioSwitch>
			<NcButton variant="tertiary" @click="openEditTag">
				<template #icon>
					<IconEdit :size="20" />
				</template>
				{{ t('mail', 'Edit') }}
			</NcButton>
		</template>
		<NcButton
			v-if="!tag.isDefaultTag || editing"
			variant="tertiary"
			@click="deleteTag">
			<template #icon>
				<DeleteIcon :size="20" />
			</template>
			{{ t('mail', 'Delete') }}
		</NcButton>
	</div>
</template>

<script>
import { showInfo } from '@nextcloud/dialogs'
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'
import { mapStores } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconEdit from 'vue-material-design-icons/PencilOutline.vue'
import DeleteIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'
import { tagBackgroundColor, tagTextColor, translateTagDisplayName, validateTag } from '../util/tag.js'

export default {
	name: 'TagItem',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcColorPicker,
		NcTextField,
		DeleteIcon,
		IconClose,
		IconEdit,
	},

	props: {
		tag: {
			type: Object,
			required: true,
		},

		envelopes: {
			// The envelopes on which this menu will act
			required: true,
			type: Array,
		},
	},

	emits: ['deleteTag'],

	data() {
		return {
			isAdded: false,
			isDarkTheme: useIsDarkTheme(),
			editing: false,
			editColor: null,
			saving: false,
			currentTagName: '',
			errorMessage: '',
		}
	},

	computed: {
		...mapStores(useMainStore),
	},

	watch: {
		currentTagName() {
			this.errorMessage = ''
		},
	},

	methods: {
		tagBackgroundColor,
		tagTextColor,
		translateTagDisplayName,
		deleteTag() {
			this.$emit('deleteTag', this.tag)
		},

		async updateColor() {
			try {
				await this.mainStore.updateTag({
					tag: this.tag,
					displayName: this.tag.displayName,
					color: this.editColor,
				})
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the tag.'))
				logger.error('could not rename tag', { error })
			}
		},

		openEditTag() {
			this.editing = true
			this.currentTagName = this.tag.displayName
			this.editColor = this.tag.color
		},

		closeEditTag() {
			this.editing = false
			this.errorMessage = ''
		},

		hasError() {
			return this.errorMessage !== ''
		},

		async renameTag(tag) {
			this.currentTagName = this.currentTagName.trim()

			const otherTags = this.mainStore.getTags
			const valid = validateTag(tag.id, this.currentTagName, otherTags)
			if (valid !== true) {
				this.errorMessage = valid
				return
			}

			this.saving = true
			this.errorMessage = ''

			try {
				await this.mainStore.updateTag({
					tag,
					displayName: this.currentTagName,
					color: this.tag.color,
				})
				this.closeEditTag()
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the tag.'))
				logger.error('could not rename tag', { error })
			} finally {
				this.saving = false
			}
		},

		isSet(imapLabel) {
			return this.envelopes.some((envelope) => (
				this.mainStore.getEnvelopeTags(envelope.databaseId).some((tag) => tag.imapLabel === imapLabel)
			))
		},

		addTag(imapLabel) {
			this.isAdded = true
			this.envelopes.forEach((envelope) => {
				this.mainStore.addEnvelopeTag({ envelope, imapLabel })
			})
		},

		removeTag(imapLabel) {
			this.isAdded = false
			this.envelopes.forEach((envelope) => {
				this.mainStore.removeEnvelopeTag({ envelope, imapLabel })
			})
		},
	},
}
</script>

<style scoped lang="scss">
.tag-group__color {
	width: calc(var(--default-grid-baseline) * 5);
	height: calc(var(--default-grid-baseline) * 5);
	border-radius: 50%;
}

.tag-group__name {
	flex: 1;
}

.tag-group {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);
	min-height: 44px;
	padding-inline: var(--default-grid-baseline);
}

.tag-group__checkbox {
	flex: 1;
	min-width: 0;
}

.tag-group__label {
	font-weight: bold;
	padding-inline: 10px;
	padding-block: 4px;
	border-radius: var(--border-radius-pill);
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
</style>
