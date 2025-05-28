<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<div>
			<h6>{{ t('mail', 'Operator') }}</h6>
		</div>
		<div>
			<NcCheckboxRadioSwitch :checked="filter.operator"
				:value="MailFilterOperator.All"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="updateFilter(MailFilterOperator.All)">
				{{ t('mail', 'If all the conditions are met, the actions will be performed') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked="filter.operator"
				:value="MailFilterOperator.Any"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="updateFilter(MailFilterOperator.Any)">
				{{ t('mail', 'If any of the conditions are met, the actions will be performed') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>
<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { MailFilterOperator } from '../../models/mailFilter.ts'

export default {
	name: 'Operator',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		filter: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			MailFilterOperator,
		}
	},
	methods: {
		updateFilter(operator) {
			this.$emit('update:operator', operator)
		},
	},
}
</script>
