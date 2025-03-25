<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal class="confirm-modal" @close="cancel">
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
	</NcModal>
</template>

<script>

import { NcButton, NcModal } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'ConfirmationModal',
	components: {
		NcButton,
		NcModal,
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
	padding: 24px 32px;

	h2 {
		margin-bottom: 20px;
		font-size: 24px;
		font-weight: 600;
	}

	&__buttons {
		display: flex;
		justify-content: flex-end;
		gap: 16px;
		margin-top: 30px;
		padding-top: 20px;

		:deep(button) {
			padding: 10px 20px;
			border-radius: 8px;
			font-weight: 500;
			transition: background 0.2s ease;

			&:hover:not(:disabled) {
				filter: brightness(1.05);
			}
		}
	}
}
</style>
