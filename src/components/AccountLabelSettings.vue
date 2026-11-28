<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="account-label-settings">
		<p class="account-label-settings__hint">
			{{ t('mail', 'Shown next to the sender in mailboxes that combine several accounts, such as All inboxes. Leave both empty to show nothing.') }}
		</p>
		<div class="account-label-settings__row">
			<NcTextField
				v-model="label"
				class="account-label-settings__label"
				:label="t('mail', 'Label')"
				:maxlength="maxLength"
				:disabled="saving"
				@keyup.enter="save"
				@blur="save" />
			<div class="account-label-settings__color">
				<span class="account-label-settings__color-label">{{ t('mail', 'Color') }}</span>
				<NcColorPicker
					v-model="pickerColor"
					@submit="onColorSubmit"
					@closed="onColorPickerClosed">
					<NcButton
						variant="tertiary"
						:aria-label="t('mail', 'Choose a color for the label')"
						:disabled="saving">
						<template #icon>
							<span
								class="account-label-settings__swatch"
								:class="{ 'account-label-settings__swatch--empty': !color }"
								:style="{ backgroundColor: color || undefined }" />
						</template>
					</NcButton>
				</NcColorPicker>
			</div>
			<NcButton
				v-if="account.unifiedLabel || account.unifiedLabelColor"
				variant="tertiary"
				:disabled="saving"
				@click="clear">
				{{ t('mail', 'Remove label') }}
			</NcButton>
		</div>
		<NcCheckboxRadioSwitch
			:modelValue="asName"
			:disabled="saving || !label.trim()"
			type="switch"
			@update:modelValue="onAsNameChange">
			{{ t('mail', 'Use label as account name') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { mapStores } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../logger.js'
import { UNIFIED_LABEL_MAX_LENGTH } from '../store/constants.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'AccountLabelSettings',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcColorPicker,
		NcTextField,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			label: this.account.unifiedLabel,
			color: this.account.unifiedLabelColor,
			pickerColor: this.account.unifiedLabelColor,
			asName: this.account.unifiedLabelAsName,
			maxLength: UNIFIED_LABEL_MAX_LENGTH,
			saving: false,
		}
	},

	computed: {
		...mapStores(useMainStore),
	},

	methods: {
		async onColorSubmit(color) {
			this.color = color
			this.pickerColor = color
			await this.save()
		},

		onColorPickerClosed() {
			this.pickerColor = this.color
		},

		async clear() {
			this.label = ''
			this.color = ''
			this.pickerColor = ''
			this.asName = false
			await this.save()
		},

		async onAsNameChange(asName) {
			this.asName = asName
			await this.save()
		},

		async save() {
			const label = this.label.trim()
			const asName = this.asName && !!label
			if (label === this.account.unifiedLabel && this.color === this.account.unifiedLabelColor && asName === this.account.unifiedLabelAsName) {
				return
			}

			this.saving = true
			try {
				await this.mainStore.setAccountUnifiedLabel({
					accountId: this.account.id,
					label,
					color: this.color,
					asName,
				})
				this.label = label
				this.asName = asName
			} catch (error) {
				logger.error('could not save the account label', { error })
				showError(t('mail', 'Could not save the label'))
				this.label = this.account.unifiedLabel
				this.color = this.account.unifiedLabelColor
				this.pickerColor = this.account.unifiedLabelColor
				this.asName = this.account.unifiedLabelAsName
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.account-label-settings {
	&__hint {
		color: var(--color-text-maxcontrast);
		margin-bottom: calc(var(--default-grid-baseline) * 2);
	}

	&__row {
		display: flex;
		align-items: flex-end;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__label {
		max-width: calc(var(--default-clickable-area) * 6);
	}

	&__color {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	&__color-label {
		color: var(--color-text-maxcontrast);
		font-size: var(--font-size-small);
	}

	&__swatch {
		display: block;
		width: calc(var(--default-grid-baseline) * 5);
		height: calc(var(--default-grid-baseline) * 5);
		border-radius: 50%;

		&--empty {
			border: 2px dashed var(--color-border-dark);
		}
	}
}
</style>
