<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<AppNavigationItem
		v-if="visible"
		:id="id"
		:key="id"
		:menu-open.sync="menuOpen"
		:title="account.emailAddress"
		:to="firstMailboxRoute"
		:exact="true"
		@update:menuOpen="onMenuToggle">
		<template #icon>
			<IconError v-if="account.error || isDisabled" :size="20" />
			<IconBullet v-else-if="bulletColor" :size="16" :fill-color="bulletColor" />
		</template>
		<!-- Actions -->
		<template #actions>
			<template v-if="isDisabled">
				<ActionText :title="t('mail', 'Provisioned account is disabled')">
					<template #icon>
						<IconInfo :size="20" />
					</template>
					{{ t('mail', 'Please login using a password to enable this account. The current session is using passwordless authentication, e.g. SSO or WebAuthn.') }}
				</ActionText>
			</template>
			<template v-else>
				<ActionText v-if="!account.isUnified" :title="t('mail', 'Quota')">
					<template #icon>
						<IconInfo
							:size="20" />
					</template>
					{{ quotaText }}
				</ActionText>
				<ActionButton
					:close-after-click="true"
					@click="showAccountSettings"
					@shortkey="toggleAccountSettings">
					<template #icon>
						<IconSettings
							:size="20" />
					</template>
					{{ t('mail', 'Account settings') }}
				</ActionButton>
				<ActionCheckbox
					:checked="account.showSubscribedOnly"
					:disabled="savingShowOnlySubscribed"
					@update:checked="changeShowSubscribedOnly">
					{{ t('mail', 'Show only subscribed mailboxes') }}
				</ActionCheckbox>
				<ActionButton v-if="!editing" @click="openCreateMailbox">
					<template #icon>
						<IconFolderAdd
							:size="20" />
					</template>
					{{ t('mail', 'Add mailbox') }}
				</ActionButton>
				<ActionInput v-if="editing" @submit.prevent.stop="createMailbox">
					<template #icon>
						<IconFolderAdd
							:size="20" />
					</template>
				</ActionInput>
				<ActionText v-if="showSaving">
					<template #icon>
						<IconLoading :size="20" />
					</template>
					{{ t('mail', 'Saving') }}
				</ActionText>
				<ActionButton v-if="!isFirst" @click="changeAccountOrderUp">
					<template #icon>
						<MenuUp
							:size="20" />
					</template>
					{{ t('mail', 'Move up') }}
				</ActionButton>
				<ActionButton v-if="!isLast" @click="changeAccountOrderDown">
					<template #icon>
						<MenuDown
							:size="20" />
					</template>
					{{ t('mail', 'Move down') }}
				</ActionButton>
				<ActionButton v-if="!account.provisioningId" @click="removeAccount">
					<template #icon>
						<IconDelete
							:size="20" />
					</template>
					{{ t('mail', 'Remove account') }}
				</ActionButton>
			</template>
		</template>
		<template #extra>
			<AccountSettings :open.sync="showSettings" :account="account" />
		</template>
	</AppNavigationItem>
</template>

<script>

import { NcAppNavigationItem as AppNavigationItem, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionText as ActionText, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'

import { calculateAccountColor } from '../util/AccountColor'
import logger from '../logger'
import { fetchQuota } from '../service/AccountService'
import AccountSettings from './AccountSettings'
import IconInfo from 'vue-material-design-icons/Information'
import IconSettings from 'vue-material-design-icons/Cog'
import IconFolderAdd from 'vue-material-design-icons/Folder'
import MenuDown from 'vue-material-design-icons/ChevronDown'
import MenuUp from 'vue-material-design-icons/ChevronUp'
import IconDelete from 'vue-material-design-icons/Delete'
import IconError from 'vue-material-design-icons/AlertCircle'
import IconBullet from 'vue-material-design-icons/CheckboxBlankCircle'

export default {
	name: 'NavigationAccount',
	components: {
		AppNavigationItem,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionText,
		AccountSettings,
		IconInfo,
		IconSettings,
		IconFolderAdd,
		MenuDown,
		MenuUp,
		IconDelete,
		IconError,
		IconBullet,
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
		}
	},
	computed: {
		visible() {
			return this.account.isUnified !== true && this.account.visible !== false
		},
		firstMailboxRoute() {
			if (this.firstMailbox && !this.isDisabled) {
				return {
					name: 'mailbox',
					params: {
						mailboxId: this.firstMailbox.databaseId,
					},
				}
			} else {
				return ''
			}
		},
		id() {
			return 'account-' + this.account.id
		},
		bulletColor() {
			return this.account.error ? undefined : calculateAccountColor(this.account.emailAddress)
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
		createMailbox(e) {
			this.editing = true
			const name = e.target.elements[1].value
			logger.info('creating mailbox ' + name)
			this.menuOpen = false
			this.$store
				.dispatch('createMailbox', { account: this.account, name })
				.then(() => logger.info(`mailbox ${name} created`))
				.catch((error) => {
					logger.error('could not create mailbox', { error })
					throw error
				})
			this.editing = false
			this.showSaving = false
		},
		openCreateMailbox() {
			this.editing = true
			this.showSaving = false
		},
		removeAccount() {
			const id = this.account.id
			logger.info('delete account', { account: this.account })
			// eslint-disable-next-line
			const dialogueText = t('mail', 'The account for {email} and cached email data will be removed from Nextcloud, but not from your email provider.', { email: this.account.emailAddress });
			OC.dialogs.confirmDestructive(
				dialogueText,
				t('mail', 'Remove account {email}', { email: this.account.emailAddress }),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('mail', 'Remove {email}', { email: this.account.emailAddress }),
					confirmClasses: 'error',
					cancel: t('mail', 'Cancel'),
				},
				(result) => {
					if (result) {
						return this.$store
							.dispatch('deleteAccount', this.account)
							.then(() => {
								this.loading.delete = true
							})
							.then(() => {
								logger.info(`account ${id} deleted, redirecting …`)

								// TODO: update store and handle this more efficiently
								location.href = generateUrl('/apps/mail')
							})
							.catch((error) => logger.error('could not delete account', { error }))
					}
					this.loading.delete = false
				}
			)
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
			this.displayAccountSettings = !this.displayAccountSettings
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
