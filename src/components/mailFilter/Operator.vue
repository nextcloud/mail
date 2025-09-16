<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="operator">
		<NcSelect :value="filter.operator"
			class="operator__select"
			:required="true"
			:label-outside="true"
			:options="availableOperators"
			:reduce="operator => operator.value"
			:clearable="false"
			@input="updateOperator($event)" />
		<NcPopover class="operator__popover" :focus-trap="false" popup-role="dialog">
			<template #trigger>
				<NcButton type="tertiary-no-background" :aria-label="t('mail', 'Help')">
					<template #icon>
						<IconInformationOutline :size="20" />
					</template>
				</NcButton>
			</template>
			<template #default>
				<div class="operator__popover__help">
					<p>
						<strong>{{ t('mail', 'contains') }}</strong>: {{ t('mail', 'A substring match. The field matches if the provided value is contained within it. For example, "report" would match "port".') }}
					</p>
					<p>
						<strong>{{ t('mail', 'matches') }}</strong>: {{ t('mail', 'A pattern match using wildcards. The "*" symbol represents any number of characters (including none), while "?" represents exactly one character. For example, "*report*" would match "Business report 2024".') }}
					</p>
				</div>
			</template>
		</NcPopover>
	</div>
</template>
<script>
import { NcButton, NcPopover, NcSelect } from '@nextcloud/vue'
import { MailFilterOperator } from '../../models/mailFilter.ts'
import IconInformationOutline from 'vue-material-design-icons/InformationOutline.vue'

export default {
	name: 'Operator',
	components: {
		IconInformationOutline,
		NcPopover,
		NcButton,
		NcSelect,
	},
	props: {
		filter: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			availableOperators: [
				{ value: MailFilterOperator.All, label: t('mail', 'If all the conditions are met, the actions will be performed') },
				{ value: MailFilterOperator.Any, label: t('mail', 'If any of the conditions are met, the actions will be performed') },
			],
		}
	},
	methods: {
		updateOperator(operator) {
			this.$emit('update:operator', operator)
		},
	},
}
</script>

<style lang="scss" scoped>
.operator {
	display: flex;
	margin-bottom: calc(var(--default-grid-baseline) * 2);
	&__select {
		margin-inline-end: var(--default-grid-baseline);
		width: 100%;
	}
	&__popover {
		width: 30px;
		&__help {
			margin: calc(var(--default-grid-baseline) * 2);
			max-width: 600px;
			& p {
				margin-bottom: 0.2em;
			}
		}
	}
}
</style>
