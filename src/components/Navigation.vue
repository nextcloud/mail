<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppNavigation>
		<NewMessageButtonHeader />
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
					<AppNavigationSpacer :key="'spacer-' + group.account.id" />
				</template>
			</template>
		</template>
		<template #footer>
			<div v-if="outboxMessages.length !== 0" class="outbox__border">
				<NavigationOutbox class="outbox" />
			</div>
			<div class="mail-settings">
				<NcButton class="mail-settings__button"
					:close-after-click="true"
					@click="showMailSettings">
					<template #icon>
						<IconSetting :size="16" />
					</template>
					{{ t('mail', 'Mail settings') }}
				</NcButton>
			</div>
		</template>
		<AppSettingsMenu :open.sync="showSettings" />
	</AppNavigation>
</template>

<script>
import { NcButton, NcAppNavigation as AppNavigation, NcAppNavigationSpacer as AppNavigationSpacer } from '@nextcloud/vue'
import NewMessageButtonHeader from './NewMessageButtonHeader.vue'

import NavigationAccount from './NavigationAccount.vue'
import NavigationAccountExpandCollapse from './NavigationAccountExpandCollapse.vue'
import NavigationMailbox from './NavigationMailbox.vue'
import NavigationOutbox from './NavigationOutbox.vue'
import IconSetting from 'vue-material-design-icons/Cog.vue'
import AppSettingsMenu from '../components/AppSettingsMenu.vue'
import { UNIFIED_ACCOUNT_ID } from '../store/constants.js'
import useOutboxStore from '../store/outboxStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'Navigation',
	components: {
		NcButton,
		AppNavigation,
		AppNavigationSpacer,
		AppSettingsMenu,
		NavigationAccount,
		NavigationAccountExpandCollapse,
		NavigationMailbox,
		NavigationOutbox,
		NewMessageButtonHeader,
		IconSetting,
	},
	data() {
		return {
			refreshing: false,
			showSettings: false,
		}
	},
	computed: {
		...mapStores(useOutboxStore),
		menu() {
			return this.$store.getters.accounts
				.filter(account => account.id !== UNIFIED_ACCOUNT_ID)
				.map(account => {
					const mailboxes = this.$store.getters.getMailboxes(account.id)
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
			return this.$store.getters.getAccount(UNIFIED_ACCOUNT_ID)
		},
		unifiedMailboxes() {
			return this.$store.getters.getMailboxes(UNIFIED_ACCOUNT_ID)
		},
		/**
		 * Whether the current session is using passwordless authentication.
		 *
		 * @return {boolean}
		 */
		passwordIsUnavailable() {
			return this.$store.getters.getPreference('password-is-unavailable', false)
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
			const accounts = this.$store.getters.accounts
			return account === accounts[1]
		},
		isLast(account) {
			const accounts = this.$store.getters.accounts
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

			return (this.passwordIsUnavailable && !!account.provisioningId) && !!this.$store.getters.masterPasswordEnabled
		},
	},
}
</script>

<style lang="scss" scoped>
.button {
	width: 44px;
	height: 44px;
	background-color: var(--color-main-background);
	border: none;
	display: inline-block;
	position: absolute;
	margin-left: 254px;
	margin-top: 13px;
	opacity: .7;
	&:hover,
	&:focus {
		opacity: 1;
		background-color:var(--color-background-hover);
	}
	&:disabled {
		cursor: not-allowed;
		opacity: .5;
		animation: rotation 2s linear;
	}
}
:deep(.app-navigation-new button) {
	width: 240px !important;
	height: 44px;
}
@keyframes rotation {
from {
	transform: rotate(-0deg);
}
to {
		transform: rotate(-360deg);
	}
}
:deep(.settings-button) {
	font-weight: bold !important;
	z-index: 1;
}
.outbox {
	margin: 6px 6px 0 6px;
	width: auto;
	&__border {
		border-top: 1px solid var(--color-background-darker);
	}
}
.mail-settings {
	padding: calc(var(--default-grid-baseline, 4px) * 2);

	&__button {
		width: 100% !important;
		justify-content: start !important;

	}
}

</style>
