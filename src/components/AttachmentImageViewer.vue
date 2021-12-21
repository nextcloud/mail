<template>
	<Modal
		:size="isMobile ? 'full' : 'large'"
		@close="$emit('close')">
		<img
			:src="url"
			class="modal__attachment">
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'
export default {
	name: 'AttachmentImageViewer',
	components: {
		Modal,
	},
	mixins: [isMobile],
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
	&--large {
		.modal-container,
		.modal__attachment {
			max-width: 85vw !important;
			max-height: 90vh !important;
		}
	}

	&--full {
		.modal-container,
		.modal__attachment {
			max-width: 95vw !important;
			max-height: 95vh !important;
		}
	}

	.modal__attachment {
		box-shadow: none !important;
		display: flex !important;
	}
}
</style>
