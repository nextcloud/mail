<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal
		v-if="view === 'main'"
		size="normal"
		label-id="delegation-modal-title"
		@close="$emit('close')">
		<div class="delegation-modal">
			<h2 id="delegation-modal-title">
				{{ t('mail', 'Delegation') }}
			</h2>

			<div class="delegation-modal__section">
				<p class="delegation-modal__description">
					{{ t('mail', 'Allow users to send, receive, and delete mail on your behalf') }}
				</p>

				<NcListItem
					v-for="user in delegates"
					:key="user.userId"
					:name="user.userId">
					<template #icon>
						<NcAvatar
							disable-menu
							:size="34"
							:user="user.userId" />
					</template>
					<template #extra-actions>
						<NcButton
							:title="t('mail', 'Revoke access')"
							:aria-label="t('mail', 'Revoke access')"
							variant="tertiary-no-background"
							@click="confirmRevoke(user)">
							<template #icon>
								<IconClose :size="20" />
							</template>
						</NcButton>
					</template>
				</NcListItem>

				<NcButton
					wide
					variant="secondary"
					@click="openAddDelegate">
					<template #icon>
						<IconPlus :size="20" />
					</template>
					{{ t('mail', 'Add delegate') }}
				</NcButton>
			</div>
		</div>
	</NcModal>

	<NcDialog
		v-else-if="view === 'add'"
		class="add-delegates-dialog"
		:open="view === 'add'"
		:name="t('mail', 'Add delegate')"
		:buttons="addDelegateButtons"
		@closing="closeDialog">
		<NcSelectUsers
			v-model="selectedUser"
			class="add-delegates-dialog__select"
			:input-label="t('mail', 'Select a user')"
			:options="userSuggestions"
			:loading="searchLoading"
			:placeholder="t('mail', 'Select a user')"
			@search="onSearch" />
		<p class="add-delegates-dialog__description">
			{{ t('mail', 'They will be able to send, receive, and delete mail on your behalf') }}
		</p>
	</NcDialog>

	<NcDialog
		v-else-if="view === 'revoke'"
		class="revoke-dialog"
		:open="view === 'revoke'"
		:name="t('mail', 'Revoke access?')"
		:buttons="revokeButtons"
		@closing="closeDialog">
		<p class="revoke-dialog__text">
			{{ revokeText }}
		</p>
	</NcDialog>
</template>

<script>
import IconCheck from '@mdi/svg/svg/check.svg?raw'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { NcAvatar, NcButton, NcDialog, NcListItem, NcModal, NcSelectUsers } from '@nextcloud/vue'
import debounce from 'lodash/fp/debounce.js'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconPlus from 'vue-material-design-icons/Plus.vue'
import logger from '../logger.js'
import { delegate, fetchDelegatedUsers, unDelegate } from '../service/DelegationService.js'

export default {
	name: 'DelegationModal',
	components: {
		NcAvatar,
		NcButton,
		NcDialog,
		NcListItem,
		NcModal,
		NcSelectUsers,
		IconClose,
		IconPlus,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			view: 'main',
			delegates: [],
			revokeUser: null,
			selectedUser: null,
			userSuggestions: [],
			searchLoading: false,
			delegating: false,
		}
	},

	computed: {
		addDelegateButtons() {
			return [
				{
					label: t('mail', 'Cancel'),
					type: 'tertiary',
					disabled: this.delegating,
					callback: () => { this.closeDialog() },
				},
				{
					label: t('mail', 'Delegate access'),
					type: 'primary',
					icon: IconCheck,
					disabled: !this.selectedUser || this.delegating,
					callback: () => { this.addDelegate() },
				},
			]
		},

		revokeButtons() {
			return [
				{
					label: t('mail', 'Cancel'),
					type: 'tertiary',
					callback: () => { this.closeDialog() },
				},
				{
					label: t('mail', 'Revoke'),
					type: 'error',
					callback: () => { this.revokeDelegate() },
				},
			]
		},

		revokeText() {
			if (!this.revokeUser) {
				return ''
			}
			return t('mail', '{userId} will no longer be able to act on your behalf', { userId: this.revokeUser.userId })
		},
	},

	async mounted() {
		await this.fetchDelegates()
	},

	methods: {
		async fetchDelegates() {
			try {
				this.delegates = await fetchDelegatedUsers(this.account.accountId)
			} catch (error) {
				logger.error('Could not fetch delegates', { error })
				showError(t('mail', 'Could not fetch delegates'))
			}
		},

		openAddDelegate() {
			this.view = 'add'
		},

		confirmRevoke(user) {
			this.revokeUser = user
			this.view = 'revoke'
		},

		onSearch(query) {
			this.debounceGetSuggestions(query.trim())
		},

		debounceGetSuggestions: debounce(300, function(...args) {
			this.getSuggestions(...args)
		}),

		async getSuggestions(search) {
			if (!search) {
				this.userSuggestions = []
				return
			}

			this.searchLoading = true
			try {
				const request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
					params: {
						format: 'json',
						itemType: 'file',
						search,
						shareTypes: [ShareType.User],
					},
				})

				const data = request.data.ocs.data
				const exact = request.data.ocs.data.exact

				const rawSuggestions = exact.users.concat(data.users)
				const currentUserId = getCurrentUser().uid
				const delegateIds = this.delegates.map((d) => d.userId)

				this.userSuggestions = rawSuggestions
					.map((result) => ({
						id: result.value.shareWith,
						displayName: result.name || result.label,
						subname: result.value.shareWith,
						user: result.value.shareWith,
					}))
					.filter((u) => u.id !== currentUserId && !delegateIds.includes(u.id))
			} catch (error) {
				logger.error('Error fetching user suggestions', { error })
			} finally {
				this.searchLoading = false
			}
		},

		async addDelegate() {
			if (!this.selectedUser) {
				return
			}

			this.delegating = true
			try {
				const delegation = await delegate(this.account.accountId, this.selectedUser.id)
				this.delegates.push(delegation)
				showSuccess(t('mail', 'Delegated access to {userId}', { userId: this.selectedUser.id }))
			} catch (error) {
				logger.error('Could not delegate access', { error })
				showError(t('mail', 'Could not delegate access'))
			} finally {
				this.delegating = false
				this.closeDialog()
			}
		},

		async revokeDelegate() {
			if (!this.revokeUser) {
				return
			}

			try {
				await unDelegate(this.account.accountId, this.revokeUser.userId)
				this.delegates = this.delegates.filter((d) => d.userId !== this.revokeUser.userId)
				showSuccess(t('mail', 'Revoked access for {userId}', { userId: this.revokeUser.userId }))
			} catch (error) {
				logger.error('Could not revoke delegation', { error })
				showError(t('mail', 'Could not revoke delegation'))
			}
		},

		closeDialog() {
			this.view = 'main'
		},
	},
}
</script>

<style lang="scss" scoped>
.delegation-modal {
	padding: var(--default-grid-baseline) calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 3);
	h2 {
		margin: 0;
		text-align: center;
	}
	&__section {
		margin-top: calc(var(--default-grid-baseline) * 3);
	}

	&__description {
		color: var(--color-text-maxcontrast);
		margin-bottom: calc(var(--default-grid-baseline) * 2);
	}
}

.add-delegates-dialog{
	&__description{
		color: var(--color-text-maxcontrast);
		padding: calc(var(--default-grid-baseline) * 2) 0;
	}
	&__select{
		width: 100%;
	}
}

.revoke-dialog {
	&__text{
		padding-bottom: calc(var(--default-grid-baseline) * 2);
	}
}
</style>
