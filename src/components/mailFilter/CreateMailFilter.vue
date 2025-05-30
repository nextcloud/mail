<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="section">
		<NcModal v-if="!showUpdateModal"
			size="normal"
			:close-on-click-outside="false"
			:name="t('mail','Update mail filter')"
			@close="closeModal">
			<form class="modal__content" @submit.prevent="createFilter">
				<NcCheckboxRadioSwitch v-for="header in headers"
					v-model="header.enable"
					type="switch">
					{{ header.label }}
				</NcCheckboxRadioSwitch>
				<br>

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
		<UpdateModal v-else-if="showUpdateModal && currentFilter"
			:filter="currentFilter"
			:account="account"
			:loading="loading"
			@update-filter="updateFilter"
			@close="closeModal" />
	</div>
</template>

<script>
import {
	NcActionButton,
	NcListItem,
	NcButton,
	NcLoadingIcon,
	NcTextField,
	NcCheckboxRadioSwitch,
	NcModal,
} from '@nextcloud/vue'
import UpdateModal from './UpdateModal.vue'
import { randomId } from '../../util/randomId.js'
import logger from '../../logger.js'
import { mapStores } from 'pinia'
import useMailFilterStore from '../../store/mailFilterStore.js'
import useMainStore from '../../store/mainStore.js'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import DeleteModal from './DeleteModal.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import Test from './Test.vue'
import Action from './Action.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import Operator from './Operator.vue'
import {
	MailFilter,
	MailFilterActionMailbox, MailFilterActionStop,
	MailFilterOperator,
	MailFilterTest, MailFilterTestField,
	MailFilterTestOperator,
} from '../../models/mailFilter'

export default {
	name: 'CreateMailFilter',
	components: {
		Operator,
		NcModal,
		NcCheckboxRadioSwitch,
		IconCheck,
		Action,
		Test,
		NcTextField,
		NcLoadingIcon,
		NcButton,
		NcListItem,
		NcActionButton,
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
			loading: true,
			errorMessage: '',
			currentFilter: null,
			headers: [],
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

		this.prepareHeaders()
	},
	methods: {
		prepareHeaders() {
			this.headers = []

			this.headers.push({
				field: 'subject',
				value: this.envelope.subject,
				label: 'Subject: ' + this.envelope.subject,
				enable: true,
			})

			for (const from of this.envelope.from) {
				this.headers.push({
					field: 'from',
					value: from.email,
					label: 'From: ' + from.email,
					enable: true,
				})
			}

			for (const to of this.envelope.to) {
				this.headers.push({
					field: 'to',
					value: to.email,
					label: 'To: ' + to.email,
					enable: true,
				})
			}
		},
		createFilter() {
			const priority = Math.max(0, ...this.filters.map((item) => item.priority ?? 0)) + 10

			const subjectTest = new MailFilterTest()
			subjectTest.field = MailFilterTestField.Subject
			subjectTest.operator = MailFilterTestOperator.Contains
			subjectTest.values = []

			const fromTest = new MailFilterTest()
			fromTest.field = MailFilterTestField.From
			fromTest.operator = MailFilterTestOperator.Is
			fromTest.values = []

			const toTest = new MailFilterTest()
			toTest.field = MailFilterTestField.To
			toTest.operator = MailFilterTestOperator.Is
			toTest.values = []

			for (const header of this.headers) {
				if (header.enable === false) {
					continue
				}

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
			this.showUpdateModal = true
			this.loading = false
		},
		openUpdateModal(filter) {
			this.currentFilter = filter
			this.showUpdateModal = true
		},
		openDeleteModal(filter) {
			this.currentFilter = filter
			this.showDeleteModal = true

		},
		async updateFilter(filter) {
			this.loading = true
			this.mailFilterStore.$patch((state) => {
				const index = state.filters.findIndex((item) => item.id === filter.id)
				logger.debug('update filter', { filter, index })

				if (index === -1) {
					state.filters.push(filter)
				} else {
					state.filters[index] = filter
				}

				state.filters.sort((a, b) => a.priority - b.priority)
			})

			try {
				await this.mailFilterStore.update(this.account.id).then(() => {
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
		async deleteFilter(filter) {
			this.loading = true

			this.mailFilterStore.$patch((state) => {
				const index = state.filters.findIndex((item) => item.id === filter.id)
				logger.debug('delete filter', { filter, index })

				if (index !== -1) {
					state.filters.splice(index, 1)
				}
			})

			try {
				await this.mailFilterStore.update(this.account.id).then(() => {
					showSuccess(t('mail', 'Filter deleted'))
				})
			} catch (e) {
				logger.error(e)
				showError(t('mail', 'Could not delete filter'))
			} finally {
				this.loading = false
			}

			await this.mainStore.fetchActiveSieveScript({ accountId: this.account.id })
		},
		closeModal() {
			this.currentFilter = null
			this.showUpdateModal = false
			this.showDeleteModal = false
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
