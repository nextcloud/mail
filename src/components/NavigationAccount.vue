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
			@update:menuOpen="onMenuToggle">
			<!-- Actions -->
			<template #actions>
				<template v-if="isDisabled">
					<ActionText :name="t('mail', 'Provisioned account is disabled')">
						<template #icon>
							<IconInfo :size="16" />
						</template>
						{{ t('mail', 'Please login using a password to enable this account. The current session is using passwordless authentication, e.g. SSO or WebAuthn.') }}
					</ActionText>
				</template>
				<template v-else>
					<ActionText v-if="!account.isUnified" :name="t('mail', 'Quota')">
						<template #icon>
							<IconInfo :size="16" />
						</template>
						{{ quotaText }}
					</ActionText>
					<ActionButton :close-after-click="true"
						@click="showAccountSettings"
						@shortkey="toggleAccountSettings">
						<template #icon>
							<IconSettings :size="16" />
						</template>
						{{ t('mail', 'Account settings') }}
					</ActionButton>
					<ActionCheckbox :checked="account.showSubscribedOnly"
						:disabled="savingShowOnlySubscribed"
						@update:checked="changeShowSubscribedOnly">
						{{ t('mail', 'Show only subscribed mailboxes') }}
					</ActionCheckbox>
					<ActionButton v-if="!editing && nameLabel" @click="openCreateMailbox">
						<template #icon>
							<IconFolderAdd :size="16" />
						</template>
						{{ t('mail', 'Add mailbox') }}
					</ActionButton>
					<ActionInput v-if="editing && nameInput"
						:value.sync="createMailboxName"
						@submit.prevent.stop="createMailbox">
						<template #icon>
							<IconFolderAdd :size="16" />
						</template>
						{{ t('mail', 'Mailbox name') }}
					</ActionInput>
					<ActionText v-if="showSaving">
						<template #icon>
							<IconLoading :size="16" />
						</template>
						{{ t('mail', 'Saving') }}
					</ActionText>
					<ActionButton v-if="!isFirst" @click="changeAccountOrderUp">
						<template #icon>
							<MenuUp :size="16" />
						</template>
						{{ t('mail', 'Move up') }}
					</ActionButton>
					<ActionButton v-if="!isLast" @click="changeAccountOrderDown">
						<template #icon>
							<MenuDown :size="16" />
						</template>
						{{ t('mail', 'Move down') }}
					</ActionButton>
					<ActionButton v-if="!account.provisioningId" @click="removeAccount">
						<template #icon>
							<IconDelete :size="16" />
						</template>
						{{ t('mail', 'Remove account') }}
					</ActionButton>
				</template>
			</template>
		</NcAppNavigationCaption>
		<AccountSettings :open="showSettings" :account="account" @update:open="toggleAccountSettings" />
	</Fragment>
</template>

<script>
import { NcAppNavigationCaption, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionText as ActionText, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { Fragment } from 'vue-frag'

import logger from '../logger.js'
import { fetchQuota } from '../service/AccountService.js'
import IconInfo from 'vue-material-design-icons/Information.vue'
import IconSettings from 'vue-material-design-icons/Cog.vue'
import IconFolderAdd from 'vue-material-design-icons/Folder.vue'
import MenuDown from 'vue-material-design-icons/ChevronDown.vue'
import MenuUp from 'vue-material-design-icons/ChevronUp.vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import { DialogBuilder } from '@nextcloud/dialogs'
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
			showSettings: false,
			createMailboxName: '',
			showMailboxes: false,
			nameInput: false,
			nameLabel: true,
		}
	},
	computed: {
		visible() {
			return this.account.isUnified !== true && this.account.visible !== false
		},
		id() {
			return 'account-' + this.account.id
		},
		quotaText() {
			if (this.quota === undefined) {
				return t('mail', 'Loading …')
			}
			if (this.quota === false) {
				return t('mail', 'Not supported by the server')
			}

			return t('mail', '{usage} of {limit} used', {
				usage: formatFileSize(this.quota.usage),
				limit: formatFileSize(this.quota.limit),
			})
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
				await this.$store.dispatch('createMailbox', {
					account: this.account, name,
				})
			} catch (error) {
				logger.error('could not create mailbox', { error })
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
								await this.$store.dispatch('deleteAccount', this.account)
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
			this.$store
				.dispatch('moveAccount', { account: this.account, up: true })
				.catch((error) => logger.error('could not move account up', { error }))
		},
		changeAccountOrderDown() {
			this.$store
				.dispatch('moveAccount', { account: this.account })
				.catch((error) => logger.error('could not move account down', { error }))
		},
		changeShowSubscribedOnly(onlySubscribed) {
			this.savingShowOnlySubscribed = true
			this.$store
				.dispatch('patchAccount', {
					account: this.account,
					data: {
						showSubscribedOnly: onlySubscribed,
					},
				})
				.then(() => {
					this.savingShowOnlySubscribed = false
					logger.info('show only subscribed mailboxes updated to ' + onlySubscribed)
				})
				.catch((error) => {
					logger.error('could not update subscription mode', { error })
					this.savingShowOnlySubscribed = false
					throw error
				})
		},
		onMenuToggle(open) {
			if (open) {
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
		 * Toggles the account settings overview
		 */
		toggleAccountSettings() {
			this.showSettings = !this.showSettings
		},
		/**
		 * Shows the account settings
		 */
		showAccountSettings() {
			this.showSettings = true
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
