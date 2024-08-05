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
import { mapStores } from 'pinia'
import useMailFilterStore from '../store/mailFilterStore'

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
		return {
			showModal: false,
			script: '',
			loading: true,
			errorMessage: '',
			currentFilter: null,
		}
	},
	computed: {
		...mapStores(useMailFilterStore),
		filters() {
			return this.mailFilterStore.getFiltersByAccountId(this.account.id)
		},
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
			this.currentFilter = { id: randomId(), name: t('mail', 'New filter') }
			this.showModal = true
		},
		updateFilter(filter) {
			this.currentFilter = structuredClone(filter)
			this.showModal = true
		},
		storeFilter() {
			// const filter = this.currentFilter.copy()
			// const index = this.filters.findIndex((item) => item.id === filter.id)
			// logger.debug('store filter', { filter, index })
			//
			// if (index === -1) {
			// 	this.filters.push(filter)
			// } else {
			// 	this.filters[index] = filter
			// }
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
