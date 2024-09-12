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
			<NcCheckboxRadioSwitch :checked.sync="filter.operator"
				value="allof"
				name="sharing_permission_radio"
				type="radio">
				{{ t('mail', 'allof') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="filter.operator"
				value="anyof"
				name="sharing_permission_radio"
				type="radio">
				{{ t('mail', 'anyof') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="filter.operator"
				value="true"
				name="sharing_permission_radio"
				type="radio">
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
		filter: {
			type: Object,
			required: true,
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
