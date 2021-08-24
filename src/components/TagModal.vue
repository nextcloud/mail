<!--
  - @copyright 2021 Greta Doci <gretadoci@gmail.com>
  -
  - @author 2021 Greta Doci <gretadoci@gmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<Modal size="large" @close="onClose">
		<div class="modal-content">
			<h2 class="tag-title">
				{{ t('mail', 'Add default tags') }}
			</h2>
			<div v-for="tag in tags" :key="tag.id" class="tag-group">
				<button class="tag-group__label"
					:style="{
						color: convertHex(tag.color, 1),
						'background-color': convertHex(tag.color, 0.15)
					}">
					{{ tag.displayName }}
				</button>
				<Actions v-if="!tag.isDefaultTag" :force-menu="true">
					<ActionButton v-if="renameTagLabel"
						icon="icon-rename"
						@click="openEditTag">
						{{ t('mail','Rename tag') }}
					</ActionButton>
					<ActionInput v-if="renameTagInput"
						icon="icon-tag"
						:value="tag.displayName"
						@submit="renameTag(tag, $event)" />
					<ActionText
						v-if="showSaving"
						icon="icon-loading-small">
						{{ t('mail', 'Saving new tag name …') }}
					</ActionText>
				</Actions>
				<button v-if="!isSet(tag.imapLabel)"
					class="tag-actions"
					@click="addTag(tag.imapLabel)">
					{{ t('mail','Add') }}
				</button>
				<button v-else
					class="tag-actions"
					@click="removeTag(tag.imapLabel)">
					{{ t('mail','Remove') }}
				</button>
			</div>
			<h2 class="tag-title">
				{{ t('mail', 'Add tag') }}
			</h2>
			<div class="create-tag">
				<button v-if="!editing"
					class="tagButton"
					@click="addTagInput">
					{{ t('mail', 'Add tag') }}
				</button>
				<ActionInput v-if="editing" icon="icon-tag" @submit="createTag" />
				<ActionText
					v-if="showSaving"
					icon="icon-loading-small">
					{{ t('mail', 'Saving tag …') }}
				</ActionText>
			</div>
		</div>
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import { showError, showInfo } from '@nextcloud/dialogs'

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
		Actions,
		ActionText,
		ActionInput,
		ActionButton,
	},
	props: {
		envelope: {
			// The envelope on which this menu will act
			required: true,
			type: Object,
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
			color: randomColor(),
		}
	},
	computed: {
		tags() {
			return this.$store.getters.getTags.filter((tag) => tag.imapLabel !== '$label1')
		},
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		isSet(imapLabel) {
			return this.$store.getters.getEnvelopeTags(this.envelope.databaseId).some(tag => tag.imapLabel === imapLabel)
		},
		addTag(imapLabel) {
			this.isAdded = true
			this.$store.dispatch('addEnvelopeTag', { envelope: this.envelope, imapLabel })
		},
		removeTag(imapLabel) {
			this.isAdded = false
			this.$store.dispatch('removeEnvelopeTag', { envelope: this.envelope, imapLabel })
		},
		addTagInput() {
			this.editing = true
			this.showSaving = false
		},
		async createTag(event) {
			this.editing = true
			const displayName = event.target.querySelector('input[type=text]').value

			try {
				await this.$store.dispatch('createTag', {
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
				await this.$store.dispatch('updateTag', {
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

	},
}

</script>

<style lang="scss" scoped>
::v-deep .modal-content {
	padding-left: 20px;
	padding-right: 20px;
	// modal jumps on the right when text is changed to 'remove'
	width: 250px;
	max-height: calc(100vh - 210px);
	overflow-y: auto;
}
.tag-title {
	margin-top: 20px;
	margin-left: 10px;
}
.tag-group {
	display: block;
	position: relative;
	margin: 0 1px;
	overflow: hidden;
	left: 4px;
}
.tag-actions {
	background-color: transparent;
	border: none;
	float: right;
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
	padding-left: 10px;
	padding-right: 10px;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 94px;
}
.app-navigation-entry-bullet-wrapper {
	width: 44px;
	height: 44px;
	display: inline-block;
	position: absolute;
	list-style: none;

	.color0 {
		width: 30px !important;
		height: 30px;
		border-radius: 50%;
		background-size: 14px;
		margin-top: -65px;
		margin-left: 180px;
		z-index: 2;
		display: flex;
		position: relative;
	}
}
.icon-colorpicker {
	background-image: var(--icon-add-fff);
}
.tagButton {
	display: inline-block;
	margin-left: 10px;
}
::v-deep .action-input {
	margin-left: -31px;
}
::v-deep .icon-tag {
	background-image: none;
}
.action-item {
	right: 8px;
	float: right;
}
</style>
