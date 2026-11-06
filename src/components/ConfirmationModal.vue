<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:name="title"
		:buttons="buttons">
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

		confirm: {
			type: Function,
			required: true,
		},

		disabled: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		buttons() {
			return [
				{
					label: t('mail', 'Cancel'),
					variant: 'tertiary',
					disabled: this.disabled,
				},
				{
					label: this.confirmText,
					variant: 'primary',
					disabled: this.disabled,

					callback: async () => {
						await this.confirm()
						return false
					},

					href: this.confirmUrl,
					rel: this.confirmUrl ? 'noopener noreferrer' : undefined,
					target: this.confirmUrl ? '_blank' : undefined,
				},
			]
		},
	},
}
</script>
