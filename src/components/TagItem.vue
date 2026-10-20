<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="tag-group">
		<button
			class="tag-group__label"
			:style="{
				color: convertHex(tag.color, 1),
				'background-color': convertHex(tag.color, 0.15),
			}">
			{{ translateTagDisplayName(tag) }}
		</button>
		<NcActions :force-menu="true" @close="closeEditTag">
			<NcActionButton
				v-if="renameTagLabel"
				@click="openEditTag">
				<template #icon>
					<IconEdit :size="22" />
				</template>
				{{ t('mail', 'Edit name or color') }}
			</NcActionButton>
			<NcColorPicker
				v-if="!renameTagLabel"
				v-model="editColor"
				class="app-navigation-entry-bullet-wrapper"
				@submit="updateColor">
				<div :style="{ backgroundColor: editColor }" class="color0 app-navigation-entry-bullet" />
			</NcColorPicker>
			<NcActionInput
				v-if="renameTagInput"
				v-model="currentTagName"
				:error="hasError()"
				:helper-text="errorMessage"
				@submit="renameTag(tag, $event)" />
			<NcActionText v-if="showSaving">
				<template #icon>
					<NcLoadingIcon :size="22" />
				</template>
				{{ t('mail', 'Saving new tag name …') }}
			</NcActionText>
			<NcActionButton
				v-if="!tag.isDefaultTag || !renameTagLabel"
				@click="deleteTag">
				<template #icon>
					<DeleteIcon :size="22" />
				</template>
				{{ t('mail', 'Delete tag') }}
			</NcActionButton>
		</NcActions>
		<button
			v-if="!isSet(tag.imapLabel)"
			class="tag-actions"
			@click="addTag(tag.imapLabel)">
			{{ t('mail', 'Set tag') }}
		</button>
		<button
			v-else
			class="tag-actions"
			@click="removeTag(tag.imapLabel)">
			{{ t('mail', 'Unset tag') }}
		</button>
	</div>
</template>

<script>
import { showInfo } from '@nextcloud/dialogs'
import { NcActionButton, NcActionInput, NcActions, NcActionText, NcColorPicker, NcLoadingIcon } from '@nextcloud/vue'
import { mapStores } from 'pinia'
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
			this.$emit('delete-tag', this.tag)
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

		async renameTag(tag, event) {
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
	display: block;
	position: relative;
	margin: 0 1px;
	overflow: hidden;
}

.tag-actions {
	background-color: transparent;
	border: none;
	float: inline-end;
	&:hover,
	&:focus {
		background-color: var(--color-border-dark);
	}
}

.tag-group__label {
	z-index: 2;
	font-weight: bold;
	border: none;
	background-color: transparent;
	padding-inline: 10px;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 94px;
}

.action-item {
	inset-inline-end: 8px;
	float: inline-end;
}

:deep(.input-field) {
	margin-top: 3px;
}
</style>
