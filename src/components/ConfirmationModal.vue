<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog class="confirm-modal" @close="cancel">
		<div class="confirm-modal">
			<h2>{{ title }}</h2>
			<slot />
			<div class="confirm-modal__buttons">
				<NcButton type="tertiary" :disabled="disabled" @click="cancel">
					{{ t('mail', 'Cancel') }}
				</NcButton>
				<NcButton :href="confirmUrl"
					:rel="confirmUrl ? 'noopener noreferrer' : false"
					:target="confirmUrl ? '_blank' : false"
					:disabled="disabled"
					type="primary"
					@click="confirm">
					{{ confirmText }}
				</NcButton>
			</div>
		</div>
	</NcDialog>
</template>

<script>

import { NcButton, NcDialog } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ConfirmationModal',
	components: {
		NcButton,
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
	methods: {
		confirm() {
			this.$emit('confirm')
		},
		cancel() {
			this.$emit('cancel')
		},
	},
}
</script>

<style lang="scss" scoped>
.confirm-modal {
	&__buttons {
		display: flex;
		justify-content: space-between;
		margin-top: 30px;

	}
}
</style>
