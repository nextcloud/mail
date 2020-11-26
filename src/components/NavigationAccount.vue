<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
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
		:icon="iconError"
		:menu-open.sync="menuOpen"
		:title="account.emailAddress"
		:to="firstMailboxRoute"
		:exact="true"
		@update:menuOpen="onMenuToggle">
		<!-- Color dot -->
		<AppNavigationIconBullet v-if="bulletColor" slot="icon" :color="bulletColor" />

		<!-- Actions -->
		<template #actions>
			<ActionText v-if="!account.isUnified" icon="icon-info" :title="t('mail', 'Quota')">
				{{ quotaText }}
			</ActionText>
			<ActionButton icon="icon-settings"
				:close-after-click="true"
				@click="showAccountSettings"
				@shortkey="toggleAccountSettings">
				{{ t('mail', 'Account settings') }}
			</ActionButton>
			<ActionCheckbox
				:checked="account.showSubscribedOnly"
				:disabled="savingShowOnlySubscribed"
				@update:checked="changeShowSubscribedOnly">
				{{ t('mail', 'Show only subscribed folders') }}
			</ActionCheckbox>
			<ActionButton v-if="!editing" icon="icon-folder" @click="openCreateMailbox">
				{{ t('mail', 'Add folder') }}
			</ActionButton>
			<ActionInput v-if="editing" icon="icon-folder" @submit.prevent.stop="createMailbox" />
			<ActionText v-if="showSaving" icon="icon-loading-small">
				{{ t('mail', 'Saving') }}
			</ActionText>
			<ActionButton v-if="!isFirst" icon="icon-triangle-n" @click="changeAccountOrderUp">
				{{ t('mail', 'Move up') }}
			</ActionButton>
			<ActionButton v-if="!isLast" icon="icon-triangle-s" @click="changeAccountOrderDown">
				{{ t('mail', 'Move down') }}
			</ActionButton>
			<ActionButton v-if="!account.provisioned" icon="icon-delete" @click="removeAccount">
				{{ t('mail', 'Remove account') }}
			</ActionButton>
		</template>
		<template #extra>
			<AccountSettings :open.sync="showSettings" :account="account" />
		</template>
	</AppNavigationItem>
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationIconBullet from '@nextcloud/vue/dist/Components/AppNavigationIconBullet'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'

import { calculateAccountColor } from '../util/AccountColor'
import logger from '../logger'
import { fetchQuota } from '../service/AccountService'
import AccountSettings from './AccountSettings'

export default {
	name: 'NavigationAccount',
	components: {
		AppNavigationItem,
		AppNavigationIconBullet,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionText,
		AccountSettings,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		firstMailbox: {
			type: Object,
			required: true,
		},
		isFirst: {
			type: Boolean,
			default: false,
		},
		isLast: {
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
			return {
				name: 'mailbox',
				params: {
					mailboxId: this.firstMailbox.databaseId,
				},
			}
		},
		id() {
			return 'account-' + this.account.id
		},
		bulletColor() {
			return this.account.error ? undefined : calculateAccountColor(this.account.emailAddress)
		},
		iconError() {
			return this.account.error ? 'icon-error' : undefined
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
