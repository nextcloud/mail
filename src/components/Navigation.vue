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
		<AppNavigationNew :text="t('mail', 'New message')"
						  buttonId="mail_new_message"
						  buttonClass="icon-add"
						  @click="onNewMessage"/>
		<ul id="accounts-list">
			<template v-for="group in menu">
				<AppNavigationItem v-for="item in group"
								   :key="item.key"
								   :item="item"/>
				<AppNavigationSpacer />
			</template>
		</ul>
		<AppNavigationSettings :title="t('mail', 'Settings')">
			<AppSettingsMenu/>
		</AppNavigationSettings>
	</AppNavigation>
</template>

<script>
	import {translate as t} from 'nextcloud-server/dist/l10n'
	import {
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
		AppNavigationSettings,
		AppNavigationSpacer
	} from 'nextcloud-vue'

	import {calculateAccountColor} from '../util/AccountColor'

	const SHOW_COLLAPSED = Object.seal([
		'inbox',
		'flagged',
		'drafts',
		'sent'
	]);

	import AppSettingsMenu from '../components/AppSettingsMenu'

	export default {
		name: "Navigation",
		components: {
			AppNavigation,
			AppNavigationItem,
			AppNavigationNew,
			AppNavigationSettings,
			AppNavigationSpacer,
			AppSettingsMenu,
		},
		computed: {
			menu() {
				return this.$store.getters.getAccounts().map(account => {
					const items = []

					const isError = account.error

					if (account.isUnified !== true && account.visible !== false) {
						items.push({
							id: 'account' + account.id,
							key: 'account' + account.id,
							text: account.emailAddress,
							bullet: isError ? undefined : calculateAccountColor(account.name), // TODO
							icon: isError ? 'icon-error' : undefined,
							router: {
								name: 'accountSettings',
								params: {
									accountId: account.id,
								}
							}
						})
					}

					const folderToEntry = folder => {
						let icon = 'folder';
						if (folder.specialRole) {
							icon = folder.specialRole;
						}

						return {
							id: 'account' + account.id + '_' + folder.id,
							key: 'account' + account.id + '_' + folder.id,
							text: folder.name,
							icon: 'icon-' + icon,
							router: {
								name: 'folder',
								params: {
									accountId: account.id,
									folderId: folder.id,
								},
								exact: false,
							},
							utils: {
								counter: folder.unread,
							},
							collapsible: true,
							opened: folder.opened,
							children: folder.folders.map(folderToEntry)
						}
					}

					this.$store.getters.getFolders(account.id)
						.filter(folder => !account.collapsed || SHOW_COLLAPSED.indexOf(folder.specialRole) !== -1)
						.map(folderToEntry)
						.forEach(i => items.push(i))

					if (!account.isUnified && account.folders.length > 0) {
						items.push({
							id: 'collapse-' + account.id,
							key: 'collapse-' + account.id,
							classes: ['collapse-folders'],
							text: account.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders'),
							action: () => this.$store.commit('toggleAccountCollapsed', account.id)
						})
					}

					return items
				})
			}
		},
		methods: {
			onNewMessage () {
				// FIXME: assumes that we're on the 'message' route already
				this.$router.push({
					name: 'message',
					params: {
						accountId: this.$route.params.accountId,
						folderId: this.$route.params.folderId,
						messageUid: 'new',
					}
				});
			}
		},
	}
</script>

<style scoped>

</style>