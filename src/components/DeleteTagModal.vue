<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<ConfirmationModal title="Delete tag"
		:disabled="deleting"
		@confirm="deleteTag"
		@cancel="onClose">
		{{ t('mail','The tag will be deleted from all messages.') }}
	</ConfirmationModal>
</template>
<script>
import { showSuccess, showInfo } from '@nextcloud/dialogs'
import ConfirmationModal from './ConfirmationModal.vue'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'DeleteTagModal',
	components: {
		ConfirmationModal,
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
		accountId: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			deleting: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		removeTag(imapLabel) {
			this.envelopes.forEach((envelope) => {
				this.mainStore.removeEnvelopeTag({ envelope, imapLabel })
			})
		},
		async deleteTag() {
			this.deleting = true
			try {
				this.removeTag(this.tag.imapLabel)
				await this.mainStore.deleteTag({
					tag: this.tag,
					accountId: this.accountId,
				})
				showSuccess(t('mail', 'Tag: {name} deleted', { name: this.tag.displayName }))
			} catch (error) {
				showInfo(t('mail', 'An error occurred, unable to delete the tag.'))
			} finally {
				this.deleting = false
				this.onClose()
			}

		},
	},
}

</script>
