<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="mail-filter-row">
		<div class="mail-filter-column">
			<NcSelect id="mail-filter-field"
				v-model="test.field"
				:required="true"
				:label-outside="true"
				:options="['subject', 'to']" />
		</div>
		<div class="mail-filter-column">
			<NcSelect id="mail-filter-operator"
				v-model="test.operator"
				:required="true"
				:label-outside="true"
				:options="['is', 'contains', 'matches']" />
		</div>
		<div class="mail-filter-column">
			<NcTextField id="mail-filter-value"
				:required="true"
				:label="t('mail', 'Value')"
				:value.sync="test.value" />
		</div>
		<div class="mail-filter-column">
			<NcButton aria-label="Delete test"
				type="tertiary-no-background"
				@click="deleteTest">
				<template #icon>
					<DeleteIcon :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>
<script>
import { NcActionButton, NcButton, NcSelect, NcTextField } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'MailFilterTest',
	components: {
		NcActionButton,
		NcButton,
		NcTextField,
		NcSelect,
		DeleteIcon,
	},
	props: {
		test: {
			type: Object,
			required: true,
		},
	},
	methods: {
		deleteTest() {
			this.$emit('delete-test', this.test)
		},
	},
}
</script>
<style lang="scss" scoped>
.mail-filter-row {
	display: flex;
	gap: 5px;
	align-items: baseline;
}

.mail-filter-column label {
	display: block;
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
