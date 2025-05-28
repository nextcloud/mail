<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="section">
		<CreateModal v-if="currentFilter === null"
			:account="account"
			:envelope="envelope"
			:loading="loading"
			@create-filter="createFilter"
			@close="closeModal" />
		<UpdateModal v-else
			:filter="currentFilter"
			:account="account"
			:loading="loading"
			@update-filter="updateFilter"
			@close="closeModal" />
	</div>
</template>

<script>
import UpdateModal from './UpdateModal.vue'
import logger from '../../logger.js'
import { mapStores } from 'pinia'
import useMailFilterStore from '../../store/mailFilterStore.ts'
import useMainStore from '../../store/mainStore.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import {
	MailFilter,
	MailFilterActionMailbox,
	MailFilterActionStop,
	MailFilterOperator,
	MailFilterCondition,
	MailFilterConditionField,
	MailFilterConditionOperator,
} from '../../models/mailFilter.ts'
import CreateModal from './CreateModal.vue'

export default {
	name: 'MailFilterFromEnvelope',
	components: {
		CreateModal,
		UpdateModal,

	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		envelope: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			showUpdateModal: false,
			script: '',
			loading: false,
			errorMessage: '',
			currentFilter: null,
		}
	},
	computed: {
		...mapStores(useMailFilterStore, useMainStore),
		filters() {
			return this.mailFilterStore.filters
		},
		scriptData() {
			return this.mainStore.getActiveSieveScript(this.account.id)
		},
	},
	watch: {
		scriptData: {
			immediate: true,
			handler(scriptData) {
				if (!scriptData) {
					return
				}

				this.script = scriptData.script
				this.loading = false
			},
		},
	},
	async mounted() {
		await this.mailFilterStore.fetch(this.account.id)
	},
	methods: {
		createFilter(headers) {
			this.loading = true

			const priority = Math.max(0, ...this.filters.map((item) => item.priority ?? 0)) + 10

			const subjectTest = new MailFilterCondition()
			subjectTest.field = MailFilterConditionField.Subject
			subjectTest.operator = MailFilterConditionOperator.Contains
			subjectTest.values = []

			const fromTest = new MailFilterCondition()
			fromTest.field = MailFilterConditionField.From
			fromTest.operator = MailFilterConditionOperator.Is
			fromTest.values = []

			const toTest = new MailFilterCondition()
			toTest.field = MailFilterConditionField.To
			toTest.operator = MailFilterConditionOperator.Is
			toTest.values = []

			for (const header of headers) {
				if (header.field === 'subject') {
					subjectTest.values = [header.value]
				}

				if (header.field === 'from') {
					fromTest.values.push(header.value)
				}

				if (header.field === 'to') {
					toTest.values.push(header.value)
				}
			}

			const moveAction = new MailFilterActionMailbox()
			moveAction.mailbox = 'INBOX'

			const stopAction = new MailFilterActionStop()

			const filter = new MailFilter()
			filter.name = t('mail', 'New filter')
			filter.operator = MailFilterOperator.All
			filter.tests = []
			filter.actions = [
				moveAction,
				stopAction,
			]
			filter.priority = priority
			filter.enable = true

			if (subjectTest.hasValues()) {
				filter.tests.push(subjectTest)
			}

			if (fromTest.hasValues()) {
				filter.tests.push(fromTest)
			}

			if (toTest.hasValues()) {
				filter.tests.push(toTest)
			}

			this.currentFilter = filter
			this.loading = false
		},
		async updateFilter(filter) {
			this.loading = true

			this.mailFilterStore.update(filter)

			try {
				await this.mailFilterStore.store(this.account.id).then(() => {
					showSuccess(t('mail', 'Filter saved'))
				})
				await this.mainStore.fetchActiveSieveScript({ accountId: this.account.id })
			} catch (e) {
				logger.error(e)
				showError(t('mail', 'Could not save filter'))
			} finally {
				this.loading = false
			}
		},
		closeModal() {
			this.$emit('close')
			this.currentFilter = null
		},
	},
}
</script>

<style lang="scss" scoped>
.section {
	display: block;
	padding: 0;
	margin-bottom: 23px;
}

textarea {
	width: 100%;
}

.primary {
	padding-left: 26px;
	background-position: 6px;
	color: var(--color-main-background);

	&:after {
		 left: 14px;
	 }
}
</style>
