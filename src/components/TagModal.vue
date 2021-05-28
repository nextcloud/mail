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
				<div class="tag-group__bg button"
					:style="{'background-color': tag.color}" />
				<span class="tag-group__label"
					:style="{color: tag.color}">
					{{ tag.displayName }} </span>
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
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import { showError } from '@nextcloud/dialogs'

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
	},
}

</script>

<style lang="scss" scoped>
::v-deep .modal-content {
	padding-left: 20px;
	padding-right: 20px;
	// modal jumps on the right when text is changed to 'remove'
	width: 220px;
}
.tag-title {
	margin-top: 20px;
	margin-left: 10px;
}
.tag-group {
	display: block;
	border: 1px solid transparent;
	border-radius: var(--border-radius-pill);
	position: relative;
	margin: 0 1px;
	overflow: hidden;
	left: 4px;
}
.tag-actions {
	margin-left: 115px;
	background-color: transparent;
	border: none;
	&:hover,
	&:focus {
		opacity: .7;
	}
}
.tag-group__bg {
	position: absolute;
	opacity: 15%;
	margin-left: 5px;
	min-width: 110px;
	cursor: default;
}
.tag-group__label {
	position: absolute;
	z-index: 2;
	font-weight: bold;
	margin: 8px 24px;
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
.action {
	list-style: none;
}
::v-deep .action-input {
	margin-left: -31px;
}
::v-deep .icon-tag {
	background-image: none;
}
</style>
