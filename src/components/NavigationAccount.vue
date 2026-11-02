<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li
		v-if="visible"
		:key="id"
		class="navigation-account-header"
		:class="{
			'navigation-account-header--folded': account.folded,
			'navigation-account-header--active': account.folded && isInboxActive,
		}">
		<h2 :id="id" class="navigation-account-header__name">
			<router-link
				v-if="inboxRoute"
				class="navigation-account-header__link"
				:to="inboxRoute"
				:title="account.emailAddress">
				{{ account.emailAddress }}
			</router-link>
			<span v-else class="navigation-account-header__link">{{ account.emailAddress }}</span>
		</h2>
		<template v-if="account.folded && inboxUnread > 0">
			<NcCounterBubble
				class="navigation-account-header__unread"
				:count="inboxUnread"
				:title="unreadLabel"
				aria-hidden="true" />
			<span class="hidden-visually">{{ unreadLabel }}</span>
		</template>
		<NcActions
			class="navigation-account-header__actions"
			:aria-label="t('mail', 'Actions for {email}', { email: account.emailAddress })">
			<template v-if="isDisabled">
				<NcActionText :name="t('mail', 'Provisioned account is disabled')">
					<template #icon>
						<IconInfo :size="20" />
					</template>
					{{ t('mail', 'Please login using a password to enable this account. The current session is using passwordless authentication, e.g. SSO or WebAuthn.') }}
				</NcActionText>
			</template>
			<template v-else>
				<NcActionText v-if="!account.isUnified && account.quotaPercentage !== null" @vue:mounted="fetchQuota">
					<template #icon>
						<IconInfo :size="20" />
					</template>
					{{ quotaText }}
				</NcActionText>
				<NcActionButton
					:closeAfterClick="true"
					@click="showAccountSettings">
					<template #icon>
						<IconSettings :size="20" />
					</template>
					{{ t('mail', 'Account settings') }}
				</NcActionButton>
				<NcActionButton
					v-if="canDelegate"
					:closeAfterClick="true"
					@click="showDelegationModal = true">
					<template #icon>
						<NcIconSvgWrapper
							:size="20"
							:title="t('mail', 'Delegate account')"
							:svg="IconDelegation" />
					</template>
					{{ t('mail', 'Delegate account') }}
				</NcActionButton>
				<NcActionCheckbox
					:modelValue="account.showSubscribedOnly"
					:disabled="savingShowOnlySubscribed"
					@update:modelValue="changeShowSubscribedOnly">
					{{ t('mail', 'Show only subscribed folders') }}
				</NcActionCheckbox>
				<NcActionButton v-if="!editing && nameLabel" @click="openCreateMailbox">
					<template #icon>
						<IconFolderAdd :size="20" />
					</template>
					{{ t('mail', 'Add folder') }}
				</NcActionButton>
				<NcActionInput
					v-if="editing && nameInput"
					v-model="createMailboxName"
					@submit.prevent.stop="createMailbox">
					<template #icon>
						<IconFolderAdd :size="20" />
					</template>
					{{ t('mail', 'Folder name') }}
				</NcActionInput>
				<NcActionText v-if="showSaving">
					<template #icon>
						<NcLoadingIcon :size="20" />
					</template>
					{{ t('mail', 'Saving') }}
				</NcActionText>
				<NcActionButton v-if="!isFirst" @click="changeAccountOrderUp">
					<template #icon>
						<MenuUp :size="20" />
					</template>
					{{ t('mail', 'Move up') }}
				</NcActionButton>
				<NcActionButton v-if="!isLast" @click="changeAccountOrderDown">
					<template #icon>
						<MenuDown :size="20" />
					</template>
					{{ t('mail', 'Move down') }}
				</NcActionButton>
				<NcActionButton v-if="!account.provisioningId && !account.isDelegated" @click="removeAccount">
					<template #icon>
						<IconDelete :size="20" />
					</template>
					{{ t('mail', 'Remove account') }}
				</NcActionButton>
			</template>
		</NcActions>
		<NcButton
			v-if="!isDisabled"
			class="navigation-account-header__toggle"
			variant="tertiary"
			:aria-label="foldLabel"
			:title="foldLabel"
			:aria-expanded="account.folded ? 'false' : 'true'"
			:disabled="savingFolded"
			@click="toggleFolded">
			<template #icon>
				<IconChevronRight class="navigation-account-header__chevron" :size="20" />
			</template>
		</NcButton>
	</li>
	<DelegationModal v-if="showDelegationModal" :account="account" @close="showDelegationModal = false" />
</template>

<script>
import { DialogBuilder, showError } from '@nextcloud/dialogs'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { mapStores } from 'pinia'
import { defineAsyncComponent } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import MenuDown from 'vue-material-design-icons/ChevronDown.vue'
import IconChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import MenuUp from 'vue-material-design-icons/ChevronUp.vue'
import IconSettings from 'vue-material-design-icons/CogOutline.vue'
import IconFolderAdd from 'vue-material-design-icons/FolderOutline.vue'
import IconInfo from 'vue-material-design-icons/InformationOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import logger from '../logger.js'
import { fetchQuota } from '../service/AccountService.js'
import useMainStore from '../store/mainStore.js'
import IconDelegation from './../../img/delegation.svg'

export default {
	name: 'NavigationAccount',
	components: {
		NcActions,
		NcButton,
		NcCounterBubble,
		NcActionButton,
		NcActionCheckbox,
		NcActionInput,
		NcActionText,
		DelegationModal: defineAsyncComponent(() => import(/* webpackChunkName: "delegation-modal" */ './DelegationModal.vue')),
		IconInfo,
		IconSettings,
		NcIconSvgWrapper,
		IconFolderAdd,
		IconChevronRight,
		MenuDown,
		MenuUp,
		IconDelete,
		NcLoadingIcon,
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
			savingFolded: false,
			quota: undefined,
			editing: false,
			showSaving: false,
			showDelegationModal: false,
			IconDelegation,
			createMailboxName: '',
			showMailboxes: false,
			nameInput: false,
			nameLabel: true,
		}
	},

	computed: {
		...mapStores(useMainStore),
		visible() {
			return this.account.isUnified !== true && this.account.visible !== false
		},

		canDelegate() {
			return !this.account.isDelegated && !this.account.provisioningId
		},

		id() {
			return 'account-' + this.account.id
		},

		foldLabel() {
			return this.account.folded
				? t('mail', 'Expand account {email}', { email: this.account.emailAddress })
				: t('mail', 'Collapse account {email}', { email: this.account.emailAddress })
		},

		inbox() {
			return this.mainStore.getMailboxes(this.account.id)
				.find((mailbox) => mailbox.specialRole === 'inbox')
		},

		inboxRoute() {
			if (this.isDisabled || !this.inbox) {
				return null
			}
			return {
				name: 'mailbox',
				params: {
					mailboxId: this.inbox.databaseId,
				},
			}
		},

		isInboxActive() {
			return this.inbox !== undefined
				&& !this.$route?.params.filter
				&& String(this.$route?.params.mailboxId) === String(this.inbox.databaseId)
		},

		inboxUnread() {
			return this.inbox?.unread ?? 0
		},

		unreadLabel() {
			return n('mail', '%n unread message in the inbox', '%n unread messages in the inbox', this.inboxUnread)
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
					account: this.account,
					name,
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
						variant: 'error',
						callback: async () => {
							this.loading.delete = true
							try {
								await this.mainStore.deleteAccount(this.account)
								logger.info(`account ${id} deleted, redirecting …`)

								// TODO: update store and handle this more efficiently
								location.href = generateUrl('/apps/mail')
							} catch (error) {
								logger.error('could not delete account', { error })
								showError(t('mail', 'could not delete account'))
							} finally {
								this.loading.delete = false
							}
						},
					},
				])
				.build()
			await dialog.show()
		},

		async toggleFolded() {
			this.savingFolded = true
			try {
				await this.mainStore.toggleAccountFolded(this.account.id)
			} catch (error) {
				logger.error('could not save folded state of account', { error })
				showError(t('mail', 'Could not save whether the account is collapsed'))
			} finally {
				this.savingFolded = false
			}
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

		async fetchQuota() {
			const quota = await fetchQuota(this.account.id)
			logger.debug('quota fetched', {
				quota,
			})

			if (quota === undefined) {
				// Server does not support this
				this.quota = false
			} else {
				this.quota = quota
			}
		},

		showAccountSettings() {
			this.mainStore.showSettingsForAccountMutation(this.account.id)
		},
	},
}
</script>

<style lang="scss" scoped>
.navigation-account-header {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) / 2);

	&:not(:first-child) {
		margin-top: calc(var(--default-clickable-area) / 2);
	}

	& + & {
		margin-top: 0;
	}

	&__toggle {
		flex-shrink: 0;
	}

	&__chevron {
		transform: rotate(90deg);
		transition: transform var(--animation-quick);
	}

	&--folded &__chevron {
		transform: rotate(0deg);
	}

	&__name {
		flex: 1 1 auto;
		min-width: 0;
		margin: 0;
		font-size: var(--default-font-size);
		font-weight: var(--font-weight-heading, bold);
		line-height: var(--default-clickable-area);
	}

	&__link {
		display: block;
		overflow: hidden;
		padding-inline: calc(var(--default-grid-baseline) * 2);
		border-radius: var(--border-radius-element);
		color: var(--color-main-text);
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	a#{&}__link:hover,
	a#{&}__link:focus-visible {
		background-color: var(--color-background-hover);
	}

	// Same highlight as an active NcAppNavigationItem
	&--active {
		position: relative;
		border-radius: var(--border-radius-element);
		background-color: color-mix(in srgb, var(--color-primary-element) 16%, transparent);

		&::before {
			content: '';
			position: absolute;
			inset-block: calc(var(--default-grid-baseline) * 2);
			inset-inline-start: 0;
			width: calc(var(--default-grid-baseline) * 3 / 4);
			border-radius: var(--border-radius-pill);
			background-color: var(--color-primary-element);
		}
	}

	&__unread {
		flex-shrink: 0;
	}

	&__actions {
		flex: 0 0 var(--default-clickable-area);
	}
}
</style>

<style lang="scss">
// Unscoped because DialogBuilder mounts outside this component; wraps the long "Remove {email}" label
.nc-generic-dialog .dialog__actions {
	flex-wrap: wrap;

	> button {
		flex: 1 auto;
	}
}
</style>
