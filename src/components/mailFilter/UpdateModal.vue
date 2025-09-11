<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal size="normal"
		:close-on-click-outside="false"
		:name="t('mail', 'New filter')"
		@close="closeModal">
		<form class="modal__content" @submit.prevent="updateFilter">
			<h2>{{ t('mail', 'New filter') }}</h2>

			<div class="filter-name">
				<NcTextField :value.sync="clone.name"
					:label="t('mail', 'Name')"
					:required="true" />
			</div>

			<div class="filter-tests">
				<h6>{{ t('mail', 'Conditions') }}</h6>

				<Operator class="filter-operator" :filter="clone" @update:operator="updateOperator" />

				<Test v-for="test in clone.tests"
					:key="test.id"
					:test="test"
					@update-test="updateTest"
					@delete-test="deleteTest" />

				<NcButton class="add-condition"
					type="secondary"
					:aria-label="t('mail', 'Add condition')"
					@click="createTest">
					{{ t('mail', 'Add condition') }}
				</NcButton>
			</div>

			<div class="filter-actions">
				<h6>{{ t('mail', 'Actions') }}</h6>

				<Action v-for="action in clone.actions"
					:key="action.id"
					:action="action"
					:account="account"
					@update-action="updateAction"
					@delete-action="deleteAction" />

				<NcButton class="add-action"
					type="secondary"
					:aria-label="t('mail', 'Add action')"
					@click="createAction">
					{{ t('mail', 'Add action') }}
				</NcButton>
			</div>

			<div class="filter-settings">
				<NcTextField :value.sync="clone.priority"
					type="number"
					:label="t('mail', 'Priority')"
					:required="true" />

				<NcCheckboxRadioSwitch :checked.sync="clone.enable" type="switch">
					{{ t('mail', 'Enable filter') }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcButton type="primary"
				native-type="submit">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<IconCheck v-else :size="20" />
				</template>
				{{ t('mail', 'Save') }}
			</NcButton>
		</form>
	</NcModal>
</template>
<script>
import { NcButton, NcCheckboxRadioSwitch, NcModal, NcTextField, NcLoadingIcon } from '@nextcloud/vue'
import Test from './Test.vue'
import Operator from './Operator.vue'
import { randomId } from '../../util/randomId.js'
import Action from './Action.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { MailFilterConditionField, MailFilterConditionOperator } from '../../models/mailFilter.ts'

export default {
	name: 'UpdateModal',
	components: {
		IconCheck,
		Action,
		Operator,
		Test,
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcModal,
		NcTextField,
	},
	props: {
		filter: {
			type: Object,
			required: true,
		},
		account: {
			type: Object,
			required: true,
		},
		loading: {
			type: Boolean,
			required: false,
		},
	},
	data() {
		return {
			clone: structuredClone(this.filter),
			boundaryElement: null,
		}
	},
	methods: {
		createTest() {
			this.clone.tests.push({ id: randomId(), field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: [] })
		},
		updateTest(test) {
			const index = this.clone.tests.findIndex((items) => items.id === test.id)
			this.$set(this.clone.tests, index, test)
		},
		deleteTest(test) {
			this.clone.tests = this.clone.tests.filter((item) => item.id !== test.id)
		},
		createAction() {
			this.clone.actions.push({ id: randomId(), type: 'fileinto' })
		},
		updateAction(action) {
			const index = this.clone.actions.findIndex((item) => item.id === action.id)
			this.$set(this.clone.actions, index, action)
		},
		updateOperator(operator) {
			this.clone.operator = operator
		},
		deleteAction(action) {
			this.clone.actions = this.clone.actions.filter((item) => item.id !== action.id)
		},
		updateFilter() {
			this.$emit('update-filter', structuredClone(this.clone))
		},
		closeModal() {
			this.$emit('close')
		},

	},
}
</script>
<style lang="scss" scoped>
.modal__content {
	margin: 20px;
}

.filter-name, .filter-tests, .filter-actions, .filter-settings {
	margin-bottom: calc(var(--default-grid-baseline) * 4)
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
	padding-inline-end: 14px;
	white-space: nowrap;
}

.add-condition, .add-action, .filter-name, .filter-settings {
	width: calc(100% - (30px + var(--default-grid-baseline)));
}
</style>
