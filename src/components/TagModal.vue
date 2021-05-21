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
						t('mail', 'To do')
					}}
					<button class="removebutton"
						@click.prevent.stop="removeTag">
						{{ t('mail','Remove') }}
					</button>
				</div>
		</div>
		</div>
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import ColorPicker from '@nextcloud/vue/dist/Components/ColorPicker'
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
	methods: {
		onClose() {
			this.$emit('close')
		},
		onToggleImportant() {
			this.$store.dispatch('addEnvelopeTag', { envelope: this.envelope, imapLabel: this.imapLabel })
		},
		removeTag() {
			this.$store.dispatch('removeEnvelopeTag', { envelope: this.envelope, tag: this.tag })
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
</style>
