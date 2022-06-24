<template>
	<Modal
		size="large"
		@close="$emit('close')">
		<img
			:src="url"
			class="modal__attachment">
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
export default {
	name: 'AttachmentImageViewer',
	components: {
		Modal,
	},
	props: {
		url: {
			type: String,
			required: true,
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep .modal-wrapper {
	.modal-container {
		background-color: unset !important;
		box-shadow: none !important;
		height: auto !important;
		top: unset !important;
	}

	.modal__attachment {
		display: flex;
		margin: 0 auto;
		max-width: min(850px, 85vw);
		max-height: 85vh;
		background-color: var(--color-main-background);
	}
}
</style>
