<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<div class="mail-filter-row">
			<p>{{ t('mail', 'Operator') }}</p>
		</div>
		<div class="mail-filter-row">
			<NcCheckboxRadioSwitch :checked="operator"
				value="allof"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="changeOperator">
				{{ t('mail', 'allof') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked="operator"
				value="anyof"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="changeOperator">
				{{ t('mail', 'anyof') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked="operator"
				value="true"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="changeOperator">
				{{ t('mail', 'true') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>
<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'MailFilterOperator',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		operator: {
			type: String,
			required: true,
		},
	},
	methods: {
		changeOperator(operator) {
			this.$emit('change-operator', operator)
		},
	},
}
</script>
<style lang="scss" scoped>
.mail-filter-row {
	display: flex;
	gap: 5px;
}
</style>
