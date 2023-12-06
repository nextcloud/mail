<!--
  - @copyright 2023 Hamza Mahjoubi <hamzamahjoubi221@proton.me>
  -
  - @author 2023 Hamza Mahjoubi <hamzamahjoubi221@proton.me>
  -
  - @license AGPL-3.0-or-later
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
	methods: {
		onClose() {
			this.$emit('close')
		},
		removeTag(imapLabel) {
			this.envelopes.forEach((envelope) => {
				this.$store.dispatch('removeEnvelopeTag', { envelope, imapLabel })
			})
		},
		async deleteTag() {
			this.deleting = true
			try {
				this.removeTag(this.tag.imapLabel)
				await this.$store.dispatch('deleteTag', {
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
