<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="section">
		<ul>
			<NcListItem v-for="filter in filters"
				:key="filter.id"
				:name="filter.name"
				:compact="true"
				@click="updateFilter(filter)">
				<template #icon>
					<IconCheck :size="20" />
				</template>
			</NcListItem>
		</ul>
		<NcButton class="app-settings-button"
			type="secondary"
			:aria-label="t('mail', 'New filter')"
			@click.prevent.stop="createFilter">
			{{ t('mail', 'New filter') }}
		</NcButton>
		<MailFilterModal v-if="currentFilter"
			:filter="currentFilter"
			:account="account"
			@store-filter="storeFilter"
			@close="closeModal" />
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading, NcActionButton, NcListItem, NcButton } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { Filter } from '../sieve/Filter'
import { Test } from '../sieve/Test'
import IconLock from 'vue-material-design-icons/Lock.vue'
import MailFilterModal from './MailFilterModal.vue'
import { randomId } from '../util/randomId'
import logger from '../logger'
import { Action } from '../sieve/Action'

export default {
	name: 'MailFilters',
	components: {
		IconLock,
		NcButton,
		ButtonVue,
		IconLoading,
		IconCheck,
		NcListItem,
		NcActionButton,
		MailFilterModal,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		const test1 = new Test()
		test1.id = randomId()
		test1.field = 'subject'
		test1.operator = 'contains'
		test1.value = 'Hello Hello'

		const test2 = new Test()
		test2.id = randomId()
		test2.field = 'to'
		test2.operator = 'is'
		test2.value = 'bob@acme.org'

		const action1 = new Action()
		action1.id = randomId()
		action1.type = 'addflag'
		action1.flag = 'Important'

		const action2 = new Action()
		action2.id = randomId()
		action2.type = 'keep'

		const filter1 = new Filter()
		filter1.id = randomId()
		filter1.name = 'Filter 1'
		filter1.tests.push(test1, test2)
		filter1.actions.push(action1, action2)

		const test3 = new Test(randomId())
		test3.field = 'subject'
		test3.operator = 'contains'
		test3.value = 'Hello Hello'

		const test4 = new Test(randomId())
		test4.field = 'to'
		test4.operator = 'is'
		test4.value = 'bob@acme.org'

		const filter2 = new Filter()
		filter2.id = randomId()
		filter2.name = 'Filter 2'
		filter2.tests.push(test3, test3)

		return {
			filters: [
				filter1,
				filter2,
			],
			showModal: false,
			script: '',
			loading: true,
			errorMessage: '',
			currentFilter: null,
		}
	},
	computed: {
		scriptData() {
			return this.$store.getters.getActiveSieveScript(this.account.id)
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
	methods: {
		createFilter() {
			this.currentFilter = new Filter()
			this.showModal = true
		},
		updateFilter(filter) {
			this.currentFilter = filter.copy()
			this.showModal = true
		},
		storeFilter() {
			const filter = this.currentFilter.copy()
			const index = this.filters.findIndex((item) => item.id === filter.id)
			logger.debug('store filter', { filter, index })

			if (index === -1) {
				this.filters.push(filter)
			} else {
				this.filters[index] = filter
			}
		},
		closeModal() {
			this.showModal = false
			this.currentFilter = null
		},
		async saveActiveScript() {
			this.loading = true
			this.errorMessage = ''

			try {
				await this.$store.dispatch('updateActiveSieveScript', {
					accountId: this.account.id,
					scriptData: {
						...this.scriptData,
						script: this.script,
					},
				})
			} catch (error) {
				if (error.response.status === 422) {
					this.errorMessage = t('mail', 'The syntax seems to be incorrect:') + ' ' + error.response.data.message
				} else {
					this.errorMessage = error.message
				}
			}

			this.loading = false
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
