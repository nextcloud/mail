<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<NcAppNavigationCaption v-if="visible"
			:id="id"
			:key="id"
			:name="account.emailAddress"
			@update:open="onMenuToggle">
			<!-- Actions -->
			<template #actions>
				<template v-if="isDisabled">
					<ActionText :name="t('mail', 'Provisioned account is disabled')">
						<template #icon>
							<IconInfo :size="20" />
						</template>
						{{ t('mail', 'Please login using a password to enable this account. The current session is using passwordless authentication, e.g. SSO or WebAuthn.') }}
					</ActionText>
				</template>
				<template v-else>
					<ActionText v-if="!account.isUnified && account.quotaPercentage !== null ">
						<template #icon>
							<IconInfo :size="20" />
						</template>
						{{ quotaText }}
					</ActionText>
					<ActionButton :close-after-click="true"
						@click="showAccountSettings(true)">
						<template #icon>
							<IconSettings :size="20" />
						</template>
						{{ t('mail', 'Account settings') }}
					</ActionButton>
					<ActionCheckbox :checked="account.showSubscribedOnly"
						:disabled="savingShowOnlySubscribed"
						@update:checked="changeShowSubscribedOnly">
						{{ t('mail', 'Show only subscribed folders') }}
					</ActionCheckbox>
					<ActionButton v-if="!editing && nameLabel" @click="openCreateMailbox">
						<template #icon>
							<IconFolderAdd :size="20" />
						</template>
						{{ t('mail', 'Add folder') }}
					</ActionButton>
					<ActionInput v-if="editing && nameInput"
						:value.sync="createMailboxName"
						@submit.prevent.stop="createMailbox">
						<template #icon>
							<IconFolderAdd :size="20" />
						</template>
						{{ t('mail', 'Folder name') }}
					</ActionInput>
					<ActionText v-if="showSaving">
						<template #icon>
							<IconLoading :size="20" />
						</template>
						{{ t('mail', 'Saving') }}
					</ActionText>
					<ActionButton v-if="!isFirst" @click="changeAccountOrderUp">
						<template #icon>
							<MenuUp :size="20" />
						</template>
						{{ t('mail', 'Move up') }}
					</ActionButton>
					<ActionButton v-if="!isLast" @click="changeAccountOrderDown">
						<template #icon>
							<MenuDown :size="20" />
						</template>
						{{ t('mail', 'Move down') }}
					</ActionButton>
					<ActionButton v-if="!account.provisioningId" @click="removeAccount">
						<template #icon>
							<IconDelete :size="20" />
						</template>
						{{ t('mail', 'Remove account') }}
					</ActionButton>
				</template>
			</template>
		</NcAppNavigationCaption>
		<AccountSettings :open="showSettings" :account="account" @update:open="showAccountSettings($event)" />
	</Fragment>
</template>

<script>
import { NcAppNavigationCaption, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionText as ActionText, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { Fragment } from 'vue-frag'

import logger from '../logger.js'
import { fetchQuota } from '../service/AccountService.js'
import IconInfo from 'vue-material-design-icons/InformationOutline.vue'
import IconSettings from 'vue-material-design-icons/CogOutline.vue'
import IconFolderAdd from 'vue-material-design-icons/FolderOutline.vue'
import MenuDown from 'vue-material-design-icons/ChevronDown.vue'
import MenuUp from 'vue-material-design-icons/ChevronUp.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import { DialogBuilder, showError } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'NavigationAccount',
	components: {
		NcAppNavigationCaption,
		Fragment,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionText,
		AccountSettings: () => import(/* webpackChunkName: "account-settings" */ './AccountSettings.vue'),
		IconInfo,
		IconSettings,
		IconFolderAdd,
		MenuDown,
		MenuUp,
		IconDelete,
		IconLoading,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		firstMailbox: {
			type: Object,
			default: () => undefined,
		},
		isFirst: {
			type: Boolean,
			default: false,
		},
		isLast: {
			type: Boolean,
			default: false,
		},
		isDisabled: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			menuOpen: false,
			loading: {
				delete: false,
			},
			savingShowOnlySubscribed: false,
			quota: undefined,
			editing: false,
			showSaving: false,
			createMailboxName: '',
			showMailboxes: false,
			nameInput: false,
			nameLabel: true,
		}
	},
	computed: {
		...mapStores(useMainStore),
		showSettings() {
			return this.mainStore.showSettingsForAccount(this.account.id)
		},
		visible() {
			return this.account.isUnified !== true && this.account.visible !== false
		},
		id() {
			return 'account-' + this.account.id
		},
		quotaText() {
			if (this.quota) {
				return t('mail', 'Used quota: {quota}% ({limit})', {
					quota: Math.ceil(this.quota.usage / this.quota.limit * 100),
					limit: formatFileSize(this.quota.limit),
				})
			}
			if (this.account.quotaPercentage) {
				return t('mail', 'Used quota: {quota}%', {
					quota: this.account.quotaPercentage,
				})
			}
			return ''
		},
	},
	methods: {
		async createMailbox(e) {
			this.nameInput = false
			this.showSaving = true
			const name = this.createMailboxName
			logger.info('creating mailbox ' + name)
			this.menuOpen = false
			try {
				await this.mainStore.createMailbox({
					account: this.account, name,
				})
			} catch (error) {
				showError(t('mail', 'Unable to create mailbox. The name likely contains invalid characters. Please try another name.'))
				logger.error('could not create folder', { error })
				throw error
			} finally {
				this.showSaving = false
				this.nameInput = false
				this.editing = false
				this.createMailboxName = ''
			}
			logger.info(`mailbox ${name} created`)
		},
		openCreateMailbox() {
			this.editing = true
			this.nameInput = true
			this.showSaving = false
		},
		async removeAccount() {
			const id = this.account.id
			logger.info('delete account', { account: this.account })
			const dialog = new DialogBuilder()
				.setName(t('mail', 'Remove account'))
				.setText(t('mail', 'The account for {email} and cached email data will be removed from Nextcloud, but not from your email provider.', { email: this.account.emailAddress }))
				.setButtons([
					{
						label: t('mail', 'Cancel'),
					},
					{
						label: t('mail', 'Remove {email}', { email: this.account.emailAddress }),
						type: 'error',
						callback: async () => {
							this.loading.delete = true
							try {
								await this.mainStore.deleteAccount(this.account)
								logger.info(`account ${id} deleted, redirecting …`)

								// TODO: update store and handle this more efficiently
								location.href = generateUrl('/apps/mail')
							} catch (error) {
								logger.error('could not delete account', { error })
							} finally {
								this.loading.delete = false
							}
						},
					},
				])
				.build()
			await dialog.show()
		},
		changeAccountOrderUp() {
			this.mainStore.moveAccount({ account: this.account, up: true })
				.catch((error) => logger.error('could not move account up', { error }))
		},
		changeAccountOrderDown() {
			this.mainStore.moveAccount({ account: this.account })
				.catch((error) => logger.error('could not move account down', { error }))
		},
		changeShowSubscribedOnly(onlySubscribed) {
			this.savingShowOnlySubscribed = true
			this.mainStore.patchAccount({
				account: this.account,
				data: {
					showSubscribedOnly: onlySubscribed,
				},
			})
				.then(() => {
					this.savingShowOnlySubscribed = false
					logger.info('show only subscribed folders updated to ' + onlySubscribed)
				})
				.catch((error) => {
					logger.error('could not update subscription mode', { error })
					this.savingShowOnlySubscribed = false
					throw error
				})
		},
		onMenuToggle(open) {
			if (open && this.account.quotaPercentage !== null) {
				console.debug('accounts menu opened, fetching quota')
				this.fetchQuota()
			}
		},
		async fetchQuota() {
			const quota = await fetchQuota(this.account.id)
			console.debug('quota fetched', {
				quota,
			})

			if (quota === undefined) {
				// Server does not support this
				this.quota = false
			} else {
				this.quota = quota
			}
		},
		/**
		 * Show the settings for the given account
		 *
		 * @param {boolean} show true to show, false to hide
		 */
		showAccountSettings(show) {
			if (show) {
				this.mainStore.showSettingsForAccountMutation(this.account.id)
			} else {
				this.mainStore.showSettingsForAccountMutation(null)
			}
		},
	},
}
</script>

<style lang="scss">
// Fix very long button labels overflowing the modal
.dialog {
	&__actions {
		flex-wrap: wrap;

		> button {
			flex: 1 auto;
		}
	}
}
</style>
