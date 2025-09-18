<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog :name="t('mail', 'Delete mail filter {filterName}?', {filterName: filter.name})"
		:open="open"
		:message="t('mail', 'Are you sure to delete the mail filter?')"
		:buttons="buttons"
		@closing="closeModal()" />
</template>

<script>
import { NcDialog } from '@nextcloud/vue'
// eslint-disable-next-line import/no-unresolved
import IconCancel from '@mdi/svg/svg/cancel.svg?raw'
// eslint-disable-next-line import/no-unresolved
import IconCheck from '@mdi/svg/svg/check.svg?raw'

export default {
	name: 'DeleteModal',
	components: {
		NcDialog,
	},
	props: {
		filter: {
			type: Object,
			required: true,
		},
		open: {
			type: Boolean,
			required: true,
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
