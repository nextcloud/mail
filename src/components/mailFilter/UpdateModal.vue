<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal
		size="normal"
		:closeOnClickOutside="false"
		:name="t('mail', 'New filter')"
		@close="closeModal">
		<form class="modal__content" @submit.prevent="updateFilter">
			<h2>{{ t('mail', 'New filter') }}</h2>

			<div class="filter-name">
				<NcTextField
					v-model="clone.name"
					:label="t('mail', 'Name')"
					:required="true" />
			</div>

			<div class="filter-tests">
				<h6>{{ t('mail', 'Conditions') }}</h6>

				<Operator class="filter-operator" :filter="clone" @update:operator="updateOperator" />

				<template v-if="!appliesToAll">
					<Test
						v-for="test in clone.tests"
						:key="test.id"
						:test="test"
						@updateTest="updateTest"
						@deleteTest="deleteTest" />

					<NcButton
						class="add-condition"
						variant="secondary"
						:aria-label="t('mail', 'Add condition')"
						@click="createTest">
						{{ t('mail', 'Add condition') }}
					</NcButton>

					<NcNoteCard v-if="submitted && !hasTests" class="filter-note" type="error">
						<p>{{ t('mail', 'Add at least one condition.') }}</p>
					</NcNoteCard>
				</template>
			</div>

			<div class="filter-actions">
				<h6>{{ t('mail', 'Actions') }}</h6>

				<Action
					v-for="action in clone.actions"
					:key="action.id"
					:action="action"
					:account="account"
					@updateAction="updateAction"
					@deleteAction="deleteAction" />

				<NcButton
					class="add-action"
					variant="secondary"
					:aria-label="t('mail', 'Add action')"
					@click="createAction">
					{{ t('mail', 'Add action') }}
				</NcButton>

				<NcNoteCard v-if="hasRedirectAction" class="filter-note" type="warning">
					<p>{{ t('mail', 'Redirected messages might be rejected by the receiving server, and cannot be read by the recipient if encrypted.') }}</p>
				</NcNoteCard>

				<NcNoteCard v-if="appliesToAll && hasMoveOrStopAction" class="filter-note" type="warning">
					<p>{{ t('mail', 'This filter moves all incoming messages or stops all filters and the autoresponder after it.') }}</p>
				</NcNoteCard>
			</div>

			<div class="filter-settings">
				<NcTextField
					v-model="clone.priority"
					type="number"
					:label="t('mail', 'Priority')"
					:required="true" />

				<NcCheckboxRadioSwitch v-model="clone.enable" type="switch">
					{{ t('mail', 'Enable filter') }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcButton
				variant="primary"
				type="submit">
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
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconCheck from 'vue-material-design-icons/Check.vue'
import Action from './Action.vue'
import Operator from './Operator.vue'
import Test from './Test.vue'
import { MailFilterActions, MailFilterConditionField, MailFilterConditionOperator, MailFilterOperator } from '../../models/mailFilter.ts'
import { randomId } from '../../util/randomId.js'

// TODO: replace with cloneDeep from es-toolkit once it is a direct dependency
function cloneFilter(filter) {
	return {
		...filter,
		tests: filter.tests.map((test) => ({ ...test, values: [...test.values] })),
		actions: filter.actions.map((action) => ({ ...action })),
	}
}

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
		NcNoteCard,
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

	emits: ['updateFilter', 'close'],

	data() {
		return {
			clone: cloneFilter(this.filter),
			boundaryElement: null,
			submitted: false,
		}
	},

	computed: {
		appliesToAll() {
			return this.clone.operator === MailFilterOperator.Always
		},

		hasTests() {
			return this.clone.tests.length > 0
		},

		hasTestWithoutValues() {
			return this.clone.tests.some((test) => test.values.length === 0)
		},

		isValid() {
			return this.appliesToAll || (this.hasTests && !this.hasTestWithoutValues)
		},

		hasMoveOrStopAction() {
			return this.clone.actions.some((action) => action.type === 'fileinto' || action.type === MailFilterActions.Stop)
		},

		hasRedirectAction() {
			return this.clone.actions.some((action) => action.type === MailFilterActions.Redirect)
		},
	},

	methods: {
		createTest() {
			this.clone.tests.push({ id: randomId(), field: MailFilterConditionField.Subject, operator: MailFilterConditionOperator.Is, values: [] })
		},

		updateTest(test) {
			const index = this.clone.tests.findIndex((items) => items.id === test.id)
			this.clone.tests[index] = test
		},

		deleteTest(test) {
			this.clone.tests = this.clone.tests.filter((item) => item.id !== test.id)
		},

		createAction() {
			this.clone.actions.push({ id: randomId(), type: 'fileinto' })
			this.clone.actions.sort((a, b) => {
				// ensure the stop action is always last
				if (a.type === 'stop') {
					return 1
				}
				if (b.type === 'stop') {
					return -1
				}
				return 0
			})
		},

		updateAction(action) {
			const index = this.clone.actions.findIndex((item) => item.id === action.id)
			this.clone.actions[index] = action
		},

		updateOperator(operator) {
			this.clone.operator = operator
		},

		deleteAction(action) {
			this.clone.actions = this.clone.actions.filter((item) => item.id !== action.id)
		},

		updateFilter() {
			this.submitted = true
			if (!this.isValid) {
				return
			}

			const filter = cloneFilter(this.clone)
			if (this.appliesToAll) {
				filter.tests = []
			}
			this.$emit('updateFilter', filter)
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

.add-condition, .add-action, .filter-name, .filter-settings, .filter-note {
	width: calc(100% - (30px + var(--default-grid-baseline)));
}

.filter-note {
	box-sizing: border-box;
}
</style>
