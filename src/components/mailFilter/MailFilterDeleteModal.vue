<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog :name="t('mail','Delete mail filter {filterName}?', {filterName: filter.name})"
		:message="t('mail', 'Are you sure to delete the mail filter?')"
		:buttons="buttons"
		@close="closeModal()" />
</template>

<script>
import { NcDialog } from '@nextcloud/vue'
import IconCancel from '@mdi/svg/svg/cancel.svg'
import IconCheck from '@mdi/svg/svg/check.svg'

export default {
	name: 'MailFilterDeleteModal',
	components: {
		IconCancel,
		IconCheck,
		NcDialog,
	},
	props: {
		filter: {
			type: Object,
			required: true,
		},
		loading: {
			type: Boolean,
			required: false,
		},
	},
	data() {
		return {
			buttons: [
				{
					label: t('mail', 'Cancel'),
					icon: IconCancel,
					callback: () => { this.closeModal() },
				},
				{
					label: t('mail', 'Delete filter'),
					type: 'error',
					icon: IconCheck,
					callback: () => { this.deleteFilter() },
				},
			],
		}
	},
	methods: {
		deleteFilter() {
			this.$emit('delete-filter', this.filter)
		},
		closeModal() {
			this.$emit('close')
		},
	},
}

</script>
