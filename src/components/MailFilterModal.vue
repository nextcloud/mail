<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal size="large"
		:close-on-click-outside="false"
		name="Name inside modal"
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
					@delete-test="deleteTest(test.id)" />
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
					@delete-action="deleteAction" />
				<NcButton class="app-settings-button"
					type="secondary"
					:aria-label="t('mail', 'New action')"
					@click="createAction">
					{{ t('mail', 'New action') }}
				</NcButton>
			</div>

			<div>
				<NcCheckboxRadioSwitch :checked.sync="clone.enable" type="switch">
					{{ t('mail', 'Enable filter') }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcButton type="primary"
				@click="storeFilter">
				<template #icon>
					<IconLoading v-if="loading" :size="16" />
					<IconCheck v-else :size="16" />
				</template>
				{{ t('mail', 'Save filter') }}
			</NcButton>
		</div>
	</NcModal>
</template>
<script>
import { NcButton, NcCheckboxRadioSwitch, NcModal, NcSelect, NcTextField } from '@nextcloud/vue'
import MailFilterTest from './MailFilterTest.vue'
import MailFilterOperator from './MailFilterOperator.vue'
import IconLock from 'vue-material-design-icons/Lock.vue'
import { Test } from '../sieve/Test'
import { randomId } from '../util/randomId'
import MailFilterAction from './MailFilterAction.vue'
import logger from '../logger'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconLoading from 'vue-material-design-icons/Loading.vue'

export default {
	name: 'MailFilterModal',
	components: {
		IconCheck,
		IconLoading,
		IconLock,
		MailFilterAction,
		MailFilterOperator,
		MailFilterTest,
		NcButton,
		NcCheckboxRadioSwitch,
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
	},
	data() {
		return {
			clone: structuredClone(this.filter),
			loading: false,
		}
	},
	methods: {
		createTest() {
			this.clone.tests.push({ id: randomId(), operator: '', value: '' })
		},
		deleteTest(testId) {
			this.clone.tests = this.clone.tests.filter((test) => test.id !== testId)
		},
		createAction() {
			this.clone.actions.push({ id: randomId(), type: '' })
		},
		deleteAction(actionId) {
			this.clone.actions = this.clone.actions.filter((action) => action.id !== actionId)
		},
		async storeFilter() {
			this.loading = true

			try {
				await this.$emit('store-filter', structuredClone(this.clone))
			} finally {
				this.loading = false
			}
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
