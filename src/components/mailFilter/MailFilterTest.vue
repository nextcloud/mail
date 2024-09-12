<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="mail-filter-rows">
		<div class="mail-filter-row">
			<div class="mail-filter-column">
				<NcSelect id="mail-filter-field"
					:value="test.field"
					:required="true"
					:label-outside="true"
					:options="['subject', 'to', 'from']"
					@input="updateTest({ field: $event })" />
			</div>
			<div class="mail-filter-column">
				<NcSelect id="mail-filter-operator"
					:value="test.operator"
					:required="true"
					:label-outside="true"
					:options="['is', 'contains', 'matches']"
					@input="updateTest({ operator: $event })" />
			</div>
			<div class="mail-filter-column">
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
		<div class="mail-filter-row">
			<div class="mail-filter-column values-list">
				<div v-for="(value, index) in test.values" :key="index" class="values-list-item">
					<NcChip :text="value"
						@close="deleteValue(index)" />
					<span v-if="(index + 1) < test.values.length">{{ t('mail', 'or') }}</span>
				</div>
			</div>
		</div>
		<div class="mail-filter-row">
			<div class="mail-filter-column">
				<NcTextField :value.sync="inputValue"
					:label="t('mail', 'Value')" />
			</div>
			<div class="mail-filter-column">
				<NcButton aria-label="Add value"
					type="tertiary-no-background"
					@click="addValue">
					<template #icon>
						<ReceiptTextPlusIcon :size="20" />
					</template>
					{{ t('mail', 'Add value') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>
<script>
import { NcActionButton, NcActions, NcButton, NcLoadingIcon, NcSelect, NcTextField } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import ReceiptTextPlusIcon from 'vue-material-design-icons/ReceiptTextPlus.vue'

export default {
	name: 'MailFilterTest',
	components: {
		NcLoadingIcon,
		NcActions,
		NcActionButton,
		NcButton,
		NcTextField,
		NcSelect,
		DeleteIcon,
		NcChip,
		CheckIcon,
		ReceiptTextPlusIcon,
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
		}
	},
	methods: {
		updateTest(properties) {
			this.$emit('update-test', { ...this.test, ...properties })
		},
		deleteTest() {
			this.$emit('delete-test', this.test)
		},
		addValue() {
			if (this.inputValue.length > 0) {
				const values = this.test.values
				values.push(this.inputValue)
				values.sort((a, b) => a.localeCompare(b))
				this.updateTest({ values })
			}
			this.inputValue = ''
		},
		deleteValue(index) {
			const values = this.test.values
			values.splice(index, 1)
			this.updateTest({ values })
		},
	},
}
</script>
<style lang="scss" scoped>
.mail-filter-rows {
	margin-bottom: calc(var(--default-grid-baseline) * 4);
}
.mail-filter-row {
	display: flex;
	gap: var(--default-grid-baseline);
	align-items: baseline;
}

.values-list {
	display: flex;
	gap: var(--default-grid-baseline);
	flex-wrap: wrap;
}

.values-list-item {
	display: flex;
	gap: var(--default-grid-baseline);
}

.mail-filter-column label {
	display: block;
}

.mail-filter-values {
	display: flex;
}

.form-group {
	margin: calc(var(--default-grid-baseline) * 4) 0;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
}

.external-label {
	display: flex;
	width: 100%;
	margin-top: 1rem;
}

.external-label label {
	padding-top: 7px;
	padding-right: 14px;
	white-space: nowrap;
}
</style>
