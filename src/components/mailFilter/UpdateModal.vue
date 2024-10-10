<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal size="large"
		:close-on-click-outside="false"
		:name="t('mail','Update mail filter')"
		@close="closeModal">
		<form class="modal__content" @submit.prevent="updateFilter">
			<div class="filter-name">
				<NcTextField :value.sync="clone.name"
					:label="t('mail', 'Filter name')"
					:required="true" />
			</div>

			<div class="filter-operator">
				<Operator :filter="clone" @update:operator="updateOperator" />
			</div>

			<div class="filter-tests">
				<h6>{{ t('mail', 'Tests') }}</h6>

				<div class="help-text">
					<p>
						{{ t('mail', 'Tests are applied to incoming emails on your mail server, targeting fields such as subject (the email\'s subject line), from (the sender), and to (the recipient). You can use the following operators to define conditions for these fields:') }}
					</p>
					<p>
						<strong>is</strong>: {{ t('mail', 'An exact match. The field must be identical to the provided value.') }}
					</p>
					<p>
						<strong>contains</strong>: {{ t('mail', 'A substring match. The field matches if the provided value is contained within it. For example, "report" would match "port".') }}
					</p>
					<p>
						<strong>matches</strong>: {{ t('mail', 'A pattern match using wildcards. The "*" symbol represents any number of characters (including none), while "?" represents exactly one character. For example, "*report*" would match "Business report 2024".') }}
					</p>
				</div>

				<Test v-for="test in clone.tests"
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
				<h6>{{ t('mail', 'Actions') }}</h6>

				<div class="help-text">
					<p>
						{{ t('mail', 'Actions are triggered when the specified tests are true. The following actions are available:') }}
					</p>
					<p>
						<strong>fileinto</strong>: {{ t('mail', 'Moves the message into a specified folder.') }}
					</p>
					<p>
						<strong>addflag</strong>: {{ t('mail', 'Adds a flag to the message.') }}
					</p>
					<p>
						<strong>stop</strong>: {{ t('mail', 'Halts the execution of the filter script. No further filters with will be processed after this action.') }}
					</p>
				</div>

				<Action v-for="action in clone.actions"
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
				native-type="submit">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="16" />
					<IconCheck v-else :size="16" />
				</template>
				{{ t('mail', 'Save filter') }}
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
		}
	},
	methods: {
		createTest() {
			this.clone.tests.push({ id: randomId(), operator: null, values: [] })
		},
		updateTest(test) {
			const index = this.clone.tests.findIndex((items) => items.id === test.id)
			this.$set(this.clone.tests, index, test)
		},
		deleteTest(test) {
			this.clone.tests = this.clone.tests.filter((item) => item.id !== test.id)
		},
		createAction() {
			this.clone.actions.push({ id: randomId(), type: null })
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

.help-text p {
	margin-bottom: 0.2em;
}
</style>
