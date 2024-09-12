<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal size="large"
		:close-on-click-outside="false"
		:name="t('mail','Update mail filter')"
		@close="closeModal">
		<div class="modal__content">
			<div class="filter-name">
				<NcTextField :value.sync="clone.name"
					:label="t('mail', 'Filter name')"
					:required="true" />
			</div>

			<div class="filter-operator">
				<MailFilterOperator :filter="clone" />
			</div>

			<div class="filter-tests">
				<p>{{ t('mail', 'Tests') }}</p>
				<MailFilterTest v-for="test in clone.tests"
					:key="test.id"
					:test="test"
					@update-test="updateTest"
					@delete-test="deleteTest" />
				<NcButton class="app-settings-button"
					type="secondary"
					:aria-label="t('mail', 'New test')"
					@click="createTest">
					{{ t('mail', 'New test') }}
				</NcButton>
			</div>

			<div class="filter-actions">
				<p>{{ t('mail', 'Actions') }}</p>
				<MailFilterAction v-for="action in clone.actions"
					:key="action.id"
					:action="action"
					:account="account"
					@update-action="updateAction"
					@delete-action="deleteAction" />
				<NcButton class="app-settings-button"
					type="secondary"
					:aria-label="t('mail', 'New action')"
					@click="createAction">
					{{ t('mail', 'New action') }}
				</NcButton>
			</div>

			<NcTextField :value.sync="clone.priority"
				type="number"
				:label="t('mail', 'Priority')"
				:required="true" />

			<NcCheckboxRadioSwitch :checked.sync="clone.enable" type="switch">
				{{ t('mail', 'Enable filter') }}
			</NcCheckboxRadioSwitch>

			<NcButton type="primary"
				@click="updateFilter">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="16" />
					<IconCheck v-else :size="16" />
				</template>
				{{ t('mail', 'Save filter') }}
			</NcButton>
		</div>
	</NcModal>
</template>
<script>
import { NcButton, NcCheckboxRadioSwitch, NcModal, NcSelect, NcTextField, NcLoadingIcon } from '@nextcloud/vue'
import MailFilterTest from './MailFilterTest.vue'
import MailFilterOperator from './MailFilterOperator.vue'
import { randomId } from '../../util/randomId'
import MailFilterAction from './MailFilterAction.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'

export default {
	name: 'MailFilterUpdateModal',
	components: {
		IconCheck,
		MailFilterAction,
		MailFilterOperator,
		MailFilterTest,
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcModal,
		NcSelect,
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
		}
	},
	methods: {
		createTest() {
			this.clone.tests.push({ id: randomId(), operator: '', values: [] })
		},
		updateTest(test) {
			const index = this.clone.tests.findIndex((items) => items.id === test.id)
			this.$set(this.clone.tests, index, test)
		},
		deleteTest(test) {
			this.clone.tests = this.clone.tests.filter((item) => item.id !== test.id)
		},
		createAction() {
			this.clone.actions.push({ id: randomId(), type: '' })
		},
		updateAction(action) {
			const index = this.clone.actions.findIndex((item) => item.id === action.id)
			this.$set(this.clone.actions, index, action)
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
	margin: 50px;
}

.modal__content h2 {
	text-align: center;
}

.filter-name, .filter-operator, .filter-tests, .filter-actions {
	margin-bottom: 8px;
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
