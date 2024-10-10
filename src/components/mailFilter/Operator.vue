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
				value="allof"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="updateFilter('allof')">
				allof ({{ t('mail', 'If all tests pass, then the actions will be executed') }})
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked="filter.operator"
				value="anyof"
				name="sharing_permission_radio"
				type="radio"
				@update:checked="updateFilter('anyof')">
				anyof ({{ t('mail', 'If one test pass, then the actions will be executed') }})
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>
<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

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
	methods: {
		updateFilter(operator) {
			this.$emit('update:operator', operator)
		},
	},
}
</script>
