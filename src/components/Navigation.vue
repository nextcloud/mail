<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<AppNavigation>
		<template #list>
			<!-- Special mailboxes first -->
			<NavigationMailbox
				v-for="mailbox in unifiedMailboxes"
				:key="'mailbox-' + mailbox.databaseId"
				:account="unifiedAccount"
				:mailbox="mailbox" />
			<NavigationOutbox v-if="showOutbox" />
			<AppNavigationSpacer />

			<!-- All other mailboxes grouped by their account -->
			<template v-for="group in menu">
				<NavigationAccount
					v-if="group.account"
					:key="group.account.id"
					:account="group.account"
					:first-mailbox="group.mailboxes[0]"
					:is-first="isFirst(group.account)"
					:is-last="isLast(group.account)" />
				<template v-for="item in group.mailboxes">
					<NavigationMailbox
						v-show="
							!group.isCollapsible ||
								!group.account.collapsed ||
								!isCollapsed(group.account, item)
						"
						:key="'mailbox-' + item.databaseId"
						:account="group.account"
						:mailbox="item" />
					<NavigationMailbox
						v-if="!group.account.isUnified && item.specialRole === 'inbox'"
						:key="item.databaseId + '-starred'"
						:account="group.account"
						:mailbox="item"
						filter="starred" />
				</template>
				<NavigationAccountExpandCollapse
					v-if="!group.account.isUnified && group.isCollapsible"
					:key="'collapse-' + group.account.id"
					:account="group.account" />
				<AppNavigationSpacer :key="'spacer-' + group.account.id" />
			</template>
		</template>
		<template #footer>
			<AppNavigationSettings :title="t('mail', 'Mail settings')">
				<template #icon>
					<IconSetting :size="20" />
				</template>
				<AppSettingsMenu />
			</AppNavigationSettings>
		</template>
	</AppNavigation>
</template>

<script>
import { NcAppNavigation as AppNavigation, NcAppNavigationSettings as AppNavigationSettings, NcAppNavigationSpacer as AppNavigationSpacer } from '@nextcloud/vue'

import NavigationAccount from './NavigationAccount'
import NavigationAccountExpandCollapse from './NavigationAccountExpandCollapse'
import NavigationMailbox from './NavigationMailbox'
import NavigationOutbox from './NavigationOutbox'
import IconSetting from 'vue-material-design-icons/Cog'
import AppSettingsMenu from '../components/AppSettingsMenu'
import { UNIFIED_ACCOUNT_ID } from '../store/constants'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationSettings,
		AppNavigationSpacer,
		AppSettingsMenu,
		NavigationAccount,
		NavigationAccountExpandCollapse,
		NavigationMailbox,
		NavigationOutbox,
		IconSetting,
	},
	data() {
		return {
			refreshing: false,
		}
	},
	computed: {
		menu() {
			return this.$store.getters.accounts
				.filter(account => account.id !== UNIFIED_ACCOUNT_ID)
				.map(account => {
					const mailboxes = this.$store.getters.getMailboxes(account.id)
					const nonSpecialRoleMailboxes = mailboxes.filter(
						(mailbox) => this.isCollapsed(account, mailbox)
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
		showOutbox() {
			return this.$store.getters['outbox/getAllMessages'].length !== 0
		},
	},
	methods: {
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
.app-navigation-spacer {
	order: 0 !important;
}
:deep(.settings-button) {
	opacity: .7 !important;
	font-weight: bold !important;
}
</style>
