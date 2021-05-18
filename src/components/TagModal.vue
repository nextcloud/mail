<template>
	<Modal size="large" @close="onClose">
		<div class="modal-content">
			<h2 class="tag-title">
				{{ t('mail', 'Add tags') }}
			</h2>
			<div class="tags">
				<div class="tagimportant button"
					style="background-color: rgb(255, 122, 102); color: rgb(0, 0, 0);"
					@click.prevent="onToggleImportant">
					{{
						t('mail', 'Important')
					}}
					<button class="removebutton"
						@click="removeTag">
						{{ t('mail','Remove') }}
					</button>
				</div>

				<div class="tagtodo button"
					style="background-color: rgb(49, 124, 204); color: rgb(255, 255, 255);"
					@click.prevent="onToggleImportant">
					{{
						t('mail', 'Work')
					}}
					<button class="removebutton"
						@click="removeTag">
						{{ t('mail','Remove') }}
					</button>
				</div>
				<div class="taglater button"
					style="background-color: rgb(241, 219, 80); color: rgb(0, 0, 0);"
					@click.prevent="onToggleImportant">
					{{
						t('mail', 'Personal')
					}}
					<button class="removebutton"
						@click="removeTag">
						{{ t('mail','Remove') }}
					</button>
				</div>
				<div class="tagfinished button"
					style="background-color: rgb(49, 204, 124); color: rgb(255, 255, 255);"
					@click.prevent="onToggleImportant">
					{{
						t('mail', 'To do')
					}}
					<button class="removebutton"
						@click="removeTag">
						{{ t('mail','Remove') }}
					</button>
				</div>
				<button @click.stop="saveNewTag">
					{{ t('mail', 'Add new tag') }}
				</button>
				<ColorPicker v-model="color" class="app-navigation-entry-bullet-wrapper">
					<div :style="{ backgroundColor: color }" class="color0 icon-colorpicker app-navigation-entry-bullet" />
				</ColorPicker>
			</div>
		</div>
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import ColorPicker from '@nextcloud/vue/dist/Components/ColorPicker'

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
		ColorPicker,
	},
	props: {
		envelope: {
			// The envelope on which this menu will act
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			color: randomColor(),
		}
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		onToggleImportant() {
			this.$store.dispatch('toggleTagImportant', { envelope: this.envelope })
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep .modal-container {
	width: 300px;
	height: 520px;
	max-width: 600px;
	max-height: 500px;
}
.tag-title {
	justify-content: center;
	display: flex;
	margin-top: 20px;
}
.tags {
	margin-left: 20px;
	line-height: 32px;
	width: min-content;
	text-align: center;
	cursor: pointer;
}
.removebutton {
	display: inline-block;
	position: absolute;
	margin-left: 50px;
	width: 100px;
	background-color: var(--color-main-background);
	border: none;
}
.app-navigation-entry-bullet-wrapper {
	width: 44px;
	height: 44px;
	display: inline-block;
	position: absolute;
	margin-left: 50px;
	margin-top: 10px;
	.color0 {
		width: 30px !important;
		height: 30px;
		border-radius: 50%;
		background-size: 14px;
	}
}
 // just a test because the colorpicket is not shown
.icon-colorpicker {
	background-image: var(--icon-add-fff);
}
</style>
