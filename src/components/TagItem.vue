<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="tag-group">
		<button class="tag-group__label"
			:style="{
				color: convertHex(tag.color, 1),
				'background-color': convertHex(tag.color, 0.15)
			}">
			{{ translateTagDisplayName(tag) }}
		</button>
		<Actions :force-menu="true">
			<NcActionButton v-if="renameTagLabel"
				@click="openEditTag">
				<template #icon>
					<IconEdit :size="22" />
				</template>
				{{ t('mail','Edit name or color') }}
			</NcActionButton>
			<NcColorPicker v-if="!renameTagLabel"
				class="app-navigation-entry-bullet-wrapper"
				:value="`#${tag.color}`"
				@input="updateColor">
				<div :style="{ backgroundColor: tag.color }" class="color0 app-navigation-entry-bullet" />
			</NcColorPicker>
			<ActionInput v-if="renameTagInput"
				:value="tag.displayName"
				@submit="renameTag(tag, $event)" />
			<ActionText v-if="showSaving">
				<template #icon>
					<IconLoading :size="22" />
				</template>
				{{ t('mail', 'Saving new tag name …') }}
			</ActionText>
			<NcActionButton v-if="!tag.isDefaultTag || !renameTagLabel"
				@click="deleteTag">
				<template #icon>
					<DeleteIcon :size="22" />
				</template>
				{{ t('mail','Delete tag') }}
			</NcActionButton>
		</Actions>
		<button v-if="!isSet(tag.imapLabel)"
			class="tag-actions"
			@click="addTag(tag.imapLabel)">
			{{ t('mail','Set tag') }}
		</button>
		<button v-else
			class="tag-actions"
			@click="removeTag(tag.imapLabel)">
			{{ t('mail','Unset tag') }}
		</button>
	</div>
</template>

<script>
import { NcColorPicker, NcActions as Actions, NcActionButton, NcActionText as ActionText, NcActionInput as ActionInput, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import { showInfo } from '@nextcloud/dialogs'
import DeleteIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import IconEdit from 'vue-material-design-icons/PencilOutline.vue'
import { translateTagDisplayName } from '../util/tag.js'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'TagItem',
	components: {
		NcColorPicker,
		Actions,
		NcActionButton,
		ActionText,
		ActionInput,
		IconLoading,
		DeleteIcon,
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
			editing: false,
			tagLabel: true,
			tagInput: false,
			showSaving: false,
			renameTagLabel: true,
			renameTagInput: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
	},
	methods: {
		translateTagDisplayName,
		deleteTag() {
			this.$emit('delete-tag', this.tag)
		},
		async updateColor(newColor) {
			this.editColor = newColor
			this.showSaving = false
			try {
				await this.mainStore.updateTag({
					tag: this.tag,
					displayName: this.tag.displayName,
					color: newColor,
				})
				this.showSaving = false
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to rename the tag.'))
				console.error(error)
				this.showSaving = true
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
		isSet(imapLabel) {
			return this.envelopes.some(
				(envelope) => (
					this.mainStore.getEnvelopeTags(envelope.databaseId).some(
						tag => tag.imapLabel === imapLabel,
					)
				),
			)
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
.app-navigation-entry-bullet-wrapper {
	width: 44px;
	height: 44px;
	display: inline-block;
	position: fixed;
	list-style: none;
	top: 18px;
	inset-inline-start: 15px;

	.color0 {
		width: 22px !important;
		height: 22px;
		border-radius: 50%;
		background-size: 14px;
		z-index: 2;
		display: flex;
		position: relative;
	}
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
