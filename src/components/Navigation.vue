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
	<AppNavigation>
		<AppNavigationNew
			:text="t('mail', 'New message')"
			button-id="mail_new_message"
			button-class="icon-add"
			@click="onNewMessage"
		/>
		<ul id="accounts-list">
			<template v-for="group in menu">
				<NavigationAccount
					v-if="group.account"
					:key="group.account.id"
					:account="group.account"
					:is-first="isFirst(group.account)"
					:is-last="isLast(group.account)"
				/>
				<template v-for="item in group.folders">
					<NavigationFolder
						v-show="!group.account.collapsed || SHOW_COLLAPSED.indexOf(item.specialRole) !== -1"
						:key="item.key"
						:account="group.account"
						:folder="item"
					/>
					<NavigationFolder
						v-if="!group.account.isUnified && item.specialRole === 'inbox'"
						v-show="!group.account.collapsed || SHOW_COLLAPSED.indexOf(item.specialRole) !== -1"
						:key="item.key + '-starred'"
						:account="group.account"
						:folder="item"
						filter="starred"
					/>
				</template>
				<NavigationAccountExpandCollapse
					v-if="!group.account.isUnified && group.account.folders.length > 0"
					:key="'collapse-' + group.account.id"
					:account="group.account"
				/>
				<AppNavigationSpacer :key="'spacer-' + group.account.id" />
			</template>
		</ul>
		<AppNavigationSettings :title="t('mail', 'Settings')">
			<AppSettingsMenu />
		</AppNavigationSettings>
	</AppNavigation>
</template>

<script>
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import AppNavigationSettings from '@nextcloud/vue/dist/Components/AppNavigationSettings'
import AppNavigationSpacer from '@nextcloud/vue/dist/Components/AppNavigationSpacer'

import NavigationAccount from './NavigationAccount'
import NavigationAccountExpandCollapse from './NavigationAccountExpandCollapse'
import NavigationFolder from './NavigationFolder'

const SHOW_COLLAPSED = Object.seal(['inbox', 'flagged', 'drafts', 'sent'])

import AppSettingsMenu from '../components/AppSettingsMenu'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationSettings,
		AppNavigationSpacer,
		AppSettingsMenu,
		NavigationAccount,
		NavigationAccountExpandCollapse,
		NavigationFolder,
	},
	data() {
		return {
			SHOW_COLLAPSED,
		}
	},
	computed: {
		menu() {
			return this.$store.getters.accounts.map(account => {
				const folders = this.$store.getters
					.getFolders(account.id)
					.filter(folder => !account.collapsed || SHOW_COLLAPSED.indexOf(folder.specialRole) !== -1)
				//.map(folderToEntry)

				return {
					id: account.id,
					account: account,
					folders,
				}
			})
		},
	},
	methods: {
		onNewMessage() {
			// FIXME: assumes that we're on the 'message' route already
			this.$router.push({
				name: 'message',
				params: {
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
					messageUid: 'new',
				},
			})
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

<style scoped></style>
