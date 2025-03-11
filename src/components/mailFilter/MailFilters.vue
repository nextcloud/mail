<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="section">
		<p>{{ t('mail', 'Take control of your email chaos. Filters help you to prioritize what matters and eliminate clutter.') }}</p>
		<ul>
			<NcListItem v-for="filter in filters"
				:key="filter.id"
				:name="filter.name"
				:compact="true"
				@click="openUpdateModal(filter)">
				<template #subname>
					<span v-if="filter.enable">{{ t('mail', 'Filter is active') }}</span>
					<span v-else>{{ t('mail', 'Filter is not active') }}</span>
				</template>
				<template #actions>
					<NcActionButton @click="openDeleteModal(filter)">
						<template #icon>
							<DeleteIcon :size="20" />
						</template>
						{{ t('mail', 'Delete filter') }}
					</NcActionButton>
				</template>
			</NcListItem>
		</ul>
		<NcButton class="app-settings-button"
			type="primary"
			:aria-label="t('mail', 'New filter')"
			@click.prevent.stop="createFilter">
			{{ t('mail', 'New filter') }}
		</NcButton>
		<UpdateModal v-if="showUpdateModal && currentFilter"
			:filter="currentFilter"
			:account="account"
			:loading="loading"
			@update-filter="updateFilter"
			@close="closeModal" />
		<DeleteModal v-if="showDeleteModal && currentFilter"
			:filter="currentFilter"
			:open="showDeleteModal"
			:loading="loading"
			@delete-filter="deleteFilter"
			@close="closeModal" />
	</div>
</template>

<script>
import { NcActionButton, NcListItem, NcButton } from '@nextcloud/vue'
import UpdateModal from './UpdateModal.vue'
import { randomId } from '../../util/randomId.js'
import logger from '../../logger.js'
import { mapStores } from 'pinia'
import useMailFilterStore from '../../store/mailFilterStore.js'
import useMainStore from '../../store/mainStore.js'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import DeleteModal from './DeleteModal.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'MailFilters',
	components: {
		NcButton,
		NcListItem,
		NcActionButton,
		UpdateModal,
		DeleteIcon,
		DeleteModal,

	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			showUpdateModal: false,
			showDeleteModal: false,
			script: '',
			loading: true,
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
		createFilter() {
			const priority = Math.max(0, ...this.filters.map((item) => item.priority ?? 0)) + 10

			this.currentFilter = {
				id: randomId(),
				name: t('mail', 'New filter'),
				enable: true,
				operator: 'allof',
				tests: [],
				actions: [],
				priority,
			}
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
