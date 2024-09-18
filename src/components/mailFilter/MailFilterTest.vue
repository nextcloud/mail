<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="mail-filter-rows">
		<div class="mail-filter-rows__row">
			<div class="mail-filter-rows__row__column">
				<NcSelect id="mail-filter-field"
					input-label="filed"
					:value="test.field"
					:required="true"
					:label-outside="true"
					:options="['subject', 'to', 'from']"
					@input="updateTest({ field: $event })" />
			</div>
			<div class="mail-filter-rows__row__column">
				<NcSelect id="mail-filter-operator"
					input-label="operator"
					:value="test.operator"
					:required="true"
					:label-outside="true"
					:options="['is', 'contains', 'matches']"
					@input="updateTest({ operator: $event })" />
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
			<NcSelect id="mail-filter-value"
				v-model="localValues"
				input-label="value"
				class="mail-filter-rows__row__select"
				:multiple="true"
				:wrap="true"
				:close-on-select="false"
				:taggable="true"
				@option:selected="updateTest({ values: localValues })"
				@option:deselected="updateTest({ values: localValues })" />
		</div>
		<hr class="solid">
	</div>
</template>
<script>
import { NcActionButton, NcActions, NcButton, NcLoadingIcon, NcSelect } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'MailFilterTest',
	components: {
		NcLoadingIcon,
		NcActions,
		NcActionButton,
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
