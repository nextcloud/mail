<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<ConfirmationModal
		:title="t('mail', 'Delete tag')"
		:disabled="deleting"
		:confirm="deleteTag">
		{{ t('mail', 'The tag will be deleted from all messages.') }}
	</ConfirmationModal>
</template>

<script>
import { showInfo, showSuccess } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import ConfirmationModal from './ConfirmationModal.vue'
import useMainStore from '../store/mainStore.js'

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
				this.$emit('closing')
			}
		},
	},
}

</script>
