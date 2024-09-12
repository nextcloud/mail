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
			type="secondary"
			:aria-label="t('mail', 'New filter')"
			@click.prevent.stop="createFilter">
			{{ t('mail', 'New filter') }}
		</NcButton>
		<MailFilterUpdateModal v-if="showUpdateModal && currentFilter"
			:filter="currentFilter"
			:account="account"
			:loading="loading"
			@update-filter="updateFilter"
			@close="closeModal" />
		<MailFilterDeleteModal v-if="showDeleteModal && currentFilter"
			:filter="currentFilter"
			:loading="loading"
			@delete-filter="deleteFilter"
			@close="closeModal" />
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading, NcActionButton, NcListItem, NcButton } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconLock from 'vue-material-design-icons/Lock.vue'
import MailFilterUpdateModal from './MailFilterUpdateModal.vue'
import { randomId } from '../../util/randomId'
import logger from '../../logger'
import { mapStores } from 'pinia'
import useMailFilterStore from '../../store/mailFilterStore'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import MailFilterDeleteModal from './MailFilterDeleteModal.vue'
import { showSuccess } from '@nextcloud/dialogs'

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
		MailFilterUpdateModal,
		DeleteIcon,
		MailFilterDeleteModal,

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
		...mapStores(useMailFilterStore),
		filters() {
			return this.mailFilterStore.filters
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
			} catch (e) {
				// TODO error toast
			} finally {
				this.loading = false
			}

			await this.$store.dispatch('fetchActiveSieveScript', { accountId: this.account.id })
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
				await this.mailFilterStore.update(this.account.id)
			} catch (e) {
				// TODO error toast
			} finally {
				this.loading = false
			}

			await this.$store.dispatch('fetchActiveSieveScript', { accountId: this.account.id })

			this.closeModal()
		},
		closeModal() {
			this.showUpdateModal = false
			this.showDeleteModal = false
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
