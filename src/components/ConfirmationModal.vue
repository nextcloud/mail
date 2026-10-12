<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:name="title"
		:buttons="buttons"
		@closing="cancel">
		<slot />
	</NcDialog>
</template>

<script>

import { t } from '@nextcloud/l10n'
import NcDialog from '@nextcloud/vue/components/NcDialog'

export default {
	name: 'ConfirmationModal',
	components: {
		NcDialog,
	},

	props: {
		title: {
			type: String,
			required: true,
		},

		confirmText: {
			type: String,
			default: t('mail', 'Confirm'),
		},

		confirmUrl: {
			type: String,
			default: undefined,
		},

		disabled: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['confirm', 'cancel'],

	computed: {
		buttons() {
			return [
				{
					label: t('mail', 'Cancel'),
					variant: 'tertiary',
					disabled: this.disabled,
					callback: this.cancel,
				},
				{
					label: this.confirmText,
					variant: 'primary',
					disabled: this.disabled,
					callback: this.confirm,
					href: this.confirmUrl,
					rel: this.confirmUrl ? 'noopener noreferrer' : undefined,
					target: this.confirmUrl ? '_blank' : undefined,
				},
			]
		},
	},

	methods: {
		async confirm() {
			return new Promise((resolve) => {
				this.$emit('confirm', null, () => resolve())
			})
		},

		cancel() {
			this.$emit('cancel')
		},
	},
}
</script>
