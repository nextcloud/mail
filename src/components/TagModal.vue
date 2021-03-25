<template>
	<Modal size="large" @close="onClose">
		<div class="modal-content">
<h2 class="tag-title">{{t('mail', 'Add tags')}}</h2>
			<div class="tags">
	<div class="tagimportant" style="background-color: rgb(255, 122, 102); color: rgb(0, 0, 0);"
				  @click.prevent="onToggleImportant">
		{{
			 t('mail', 'Important')
		}}
	</div>
	<div class="tagtodo" style="background-color: rgb(49, 124, 204); color: rgb(255, 255, 255);"
		@click.prevent="onToggleImportant">
		{{
			t('mail', 'To do')
		}}
	</div>
	<div class="taglater" style="background-color: rgb(241, 219, 80); color: rgb(0, 0, 0);"
		@click.prevent="onToggleImportant">
		{{
			t('mail', 'Later')
		}}
	</div>
	<div class="tagfinished" style="background-color: rgb(49, 204, 124); color: rgb(255, 255, 255);"
		@click.prevent="onToggleImportant">
		{{
			t('mail', 'Finished')
		}}
	</div>
			</div>
	</div>
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'

export default {
	name: 'TagModal',
	components: {
		Modal,
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		addLabelToEnvelope(newLabel) {
			this.labels.push(newLabel)
			const data = {
				envelope: this.envelope,
				labelId: newLabel.id,
			}
			this.$store.dispatch('addLabel', data)
		},
	}
}
</script>


<style lang="scss" scoped>
::v-deep .modal-container {
	width: calc(100vw - 120px) !important;
	height: calc(100vh - 120px) !important;
	max-width: 600px !important;
	max-height: 500px !important;
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
}
.tagimportant,
.taglater,
.tagtodo,
.tagfinished {
	border-radius: 20px;
	border: 2px solid var(--color-main-background);
	width: 100px;
	text-align: center;
}
</style>
