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
			<NcCheckboxRadioSwitch :checked="filter.operator"
				value="allof"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="updateFilter('allof')">
				{{ t('mail', 'allof') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked="filter.operator"
				value="anyof"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="updateFilter('anyof')">
				{{ t('mail', 'anyof') }}
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
		filter: {
			type: Object,
			required: true,
		},
	},
	methods: {
		updateFilter(operator) {
			this.$emit('update:operator', operator)
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
