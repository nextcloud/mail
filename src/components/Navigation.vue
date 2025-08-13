<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppNavigation class="mail-navigation">
		<template #search>
			<NewMessageButtonHeader class="mail-navigation__new-message-button" />
		</template>
		<template #list>
			<!-- Special mailboxes first -->
			<NavigationMailbox v-for="mailbox in unifiedMailboxes"
				:key="'mailbox-' + mailbox.databaseId"
				:account="unifiedAccount"
				:mailbox="mailbox" />

			<!-- All other mailboxes grouped by their account -->
			<template v-for="group in menu">
				<NavigationAccount v-if="group.account"
					:key="group.account.id"
					:account="group.account"
					:first-mailbox="group.mailboxes[0]"
					:is-first="isFirst(group.account)"
					:is-last="isLast(group.account)"
					:is-disabled="isDisabled(group.account)" />
				<template v-if="!isDisabled(group.account)">
					<template v-for="item in group.mailboxes">
						<NavigationMailbox v-show="
								!group.isCollapsible ||
									!group.account.collapsed ||
									!isCollapsed(group.account, item)
							"
							:key="'mailbox-' + item.databaseId"
							:account="group.account"
							:mailbox="item" />
						<NavigationMailbox v-if="!group.account.isUnified && item.specialRole === 'inbox'"
							:key="item.databaseId + '-starred'"
							:account="group.account"
							:mailbox="item"
							filter="starred" />
					</template>
					<NavigationAccountExpandCollapse v-if="!group.account.isUnified && group.isCollapsible"
						:key="'collapse-' + group.account.id"
						:account="group.account" />
				</template>
			</template>
		</template>
		<template #footer>
			<div v-if="outboxMessages.length !== 0" class="outbox__border">
				<NavigationOutbox class="outbox" />
			</div>
			<div class="mail-settings">
				<AppNavigationItem class="mail-settings__button"
					:close-after-click="true"
					:name="t('mail', 'Mail settings')"
					@click="showMailSettings">
					<template #icon>
						<IconSetting :size="20" />
					</template>
				</AppNavigationItem>
			</div>
		</template>
		<AppSettingsMenu :open.sync="showSettings" />
	</AppNavigation>
</template>

<script>
import { NcAppNavigation as AppNavigation, NcAppNavigationItem as AppNavigationItem } from '@nextcloud/vue'
import NewMessageButtonHeader from './NewMessageButtonHeader.vue'

import NavigationAccount from './NavigationAccount.vue'
import NavigationAccountExpandCollapse from './NavigationAccountExpandCollapse.vue'
import NavigationMailbox from './NavigationMailbox.vue'
import NavigationOutbox from './NavigationOutbox.vue'
import IconSetting from 'vue-material-design-icons/CogOutline.vue'
import AppSettingsMenu from '../components/AppSettingsMenu.vue'
import { UNIFIED_ACCOUNT_ID } from '../store/constants.js'
import useOutboxStore from '../store/outboxStore.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppSettingsMenu,
		NavigationAccount,
		NavigationAccountExpandCollapse,
		NavigationMailbox,
		NavigationOutbox,
		NewMessageButtonHeader,
		IconSetting,
		AppNavigationItem,
	},
	data() {
		return {
			refreshing: false,
			showSettings: false,
		}
	},
	computed: {
		...mapStores(useOutboxStore, useMainStore),
		menu() {
			return this.mainStore.getAccounts
				.filter(account => account.id !== UNIFIED_ACCOUNT_ID)
				.map(account => {
					const mailboxes = this.mainStore.getMailboxes(account.id)
					const nonSpecialRoleMailboxes = mailboxes.filter(
						(mailbox) => this.isCollapsed(account, mailbox),
					)
					const isCollapsible = nonSpecialRoleMailboxes.length > 1

					return {
						id: account.id,
						account,
						mailboxes,
						isCollapsible,
					}
				})
		},
		unifiedAccount() {
			return this.mainStore.getAccount(UNIFIED_ACCOUNT_ID)
		},
		unifiedMailboxes() {
			return this.mainStore.getMailboxes(UNIFIED_ACCOUNT_ID)
		},
		/**
		 * Whether the current session is using passwordless authentication.
		 *
		 * @return {boolean}
		 */
		passwordIsUnavailable() {
			return this.mainStore.getPreference('password-is-unavailable', false)
		},
		outboxMessages() {
			return this.outboxStore.getAllMessages
		},
	},
	methods: {
		showMailSettings() {
			this.showSettings = true
		},
		isCollapsed(account, mailbox) {
			if (mailbox.specialRole === 'inbox') {
				// INBOX is always visible
				return false
			}

			if (mailbox.databaseId === account.draftsMailboxId
				|| mailbox.databaseId === account.sentMailboxId
				|| mailbox.databaseId === account.trashMailboxId) {
				// Special folders are always visible
				return false
			}

			return true
		},
		isFirst(account) {
			const accounts = this.mainStore.getAccounts
			return account === accounts[1]
		},
		isLast(account) {
			const accounts = this.mainStore.getAccounts
			return account === accounts[accounts.length - 1]
		},
		/**
		 * Disable provisioned accounts when no password is available.
		 * Loading messages of those accounts will fail and an endless spinner will be shown.
		 *
		 * @param {object} account Account object
		 * @return {boolean} True if the account should be disabled
		 */
		isDisabled(account) {

			return (this.passwordIsUnavailable && !!account.provisioningId) && !!this.mainStore.masterPasswordEnabled
		},
	},
}
</script>

<style lang="scss" scoped>
@keyframes rotation {
from {
	transform: rotate(-0deg);
}
to {
		transform: rotate(-360deg);
	}
}

.mail-navigation {
	&__new-message-button {
		padding: calc(var(--default-grid-baseline, 4px) * 2);
	}
}

.outbox {
	padding: calc(var(--default-grid-baseline, 4px) * 2);
	padding-bottom: 0;

	width: auto;
	&__border {
		border-top: 1px solid var(--color-background-darker);
	}
}

.mail-settings {
	padding: calc(var(--default-grid-baseline, 4px) * 2);
	padding-top: 0;

	&__button {
		display: flex;
		width: 100% !important;
		justify-content: start !important;
	}
}

.v-popper__inner {
	height: unset !important;
}

</style>
