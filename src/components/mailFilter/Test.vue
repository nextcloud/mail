<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="mail-filter-rows">
		<div class="mail-filter-rows__row">
			<div class="mail-filter-rows__row__column">
				<NcSelect input-label="field"
					:value="test.field"
					:required="true"
					:label-outside="true"
					:options="fields"
					@input="updateTest({ field: $event })">
					<template #selected-option="{ label }">
						{{ getLabelForField(label) }}
					</template>
					<template #option="{ label }">
						{{ getLabelForField(label) }}
					</template>
				</NcSelect>
			</div>
			<div class="mail-filter-rows__row__column">
				<NcSelect input-label="operator"
					:value="test.operator"
					:required="true"
					:label-outside="true"
					:options="operators"
					@input="updateTest({ operator: $event })">
					<template #selected-option="{ label }">
						{{ getLabelForOperator(label) }}
					</template>
					<template #option="{ label }">
						{{ getLabelForOperator(label) }}
					</template>
				</NcSelect>
			</div>
			<div class="mail-filter-rows__row__column">
				<NcButton aria-label="Delete action"
					type="tertiary-no-background"
					@click="deleteTest">
					<template #icon>
						<DeleteIcon :size="20" />
					</template>
					{{ t('mail', 'Delete test') }}
				</NcButton>
			</div>
		</div>
		<div class="mail-filter-rows__row">
			<NcSelect v-model="localValues"
				input-label="value"
				class="mail-filter-rows__row__select"
				:multiple="true"
				:wrap="true"
				:close-on-select="false"
				:taggable="true"
				:required="true"
				@option:selected="updateTest({ values: localValues })"
				@option:deselected="updateTest({ values: localValues })" />
		</div>
		<hr class="solid">
	</div>
</template>
<script>
import { NcButton, NcSelect } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/DeleteOutline.vue'
import { MailFilterConditionField, MailFilterConditionOperator } from '../../models/mailFilter.ts'

export default {
	name: 'Test',
	components: {
		NcButton,
		NcSelect,
		DeleteIcon,
	},
	props: {
		test: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			inputValue: '',
			localValues: [],
			fields: [
				MailFilterConditionField.Subject,
				MailFilterConditionField.From,
				MailFilterConditionField.To,
			],
			operators: [
				MailFilterConditionOperator.Is,
				MailFilterConditionOperator.Contains,
				MailFilterConditionOperator.Matches,
			],
		}
	},
	mounted() {
		this.localValues = [...this.test.values]
	},
	methods: {
		updateTest(properties) {
			this.$emit('update-test', { ...this.test, ...properties })
		},
		deleteTest() {
			this.$emit('delete-test', this.test)
		},
		getLabelForField(field) {
			switch (field) {
			case MailFilterConditionField.Subject:
				return t('mail', 'Subject')
			case MailFilterConditionField.From:
				return t('mail', 'Sender')
			case MailFilterConditionField.To:
				return t('mail', 'Recipient')
			}
			return field
		},
		getLabelForOperator(field) {
			switch (field) {
			case MailFilterConditionOperator.Is:
				return t('mail', 'is exactly')
			case MailFilterConditionOperator.Contains:
				return t('mail', 'contains')
			case MailFilterConditionOperator.Matches:
				return t('mail', 'matches')
			}
			return field
		},
	},
}
</script>
<style lang="scss" scoped>
.solid {
	margin: calc(var(--default-grid-baseline) * 4) 0 0 0;
}
.mail-filter-rows {
	margin-bottom: calc(var(--default-grid-baseline) * 4);
	&__row {
		display: flex;
		gap: var(--default-grid-baseline);
		align-items: baseline;
		&__column {
			display: block;
			flex-grow: 1;
		}
		&__column *{
			width: 100%;
		}
		&__select {
			max-width: 100% !important;
			width: 100%;
		}

	}
}

.values-list {
	display: flex;
	gap: var(--default-grid-baseline);
	flex-wrap: wrap;
	&__item {
		display: flex;
		gap: var(--default-grid-baseline);
	}
}
</style>
