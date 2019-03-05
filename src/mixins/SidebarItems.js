import {translate as t} from 'nextcloud-server/dist/l10n'

import {calculateAccountColor} from '../util/AccountColor'

const SHOW_COLLAPSED = Object.seal([
	'inbox',
	'flagged',
	'drafts',
	'sent'
]);

export default {
	methods: {
		buildMenu () {
			let items = [];

			let accounts = this.$store.getters.getAccounts();
			for (let id in accounts) {
				const account = accounts[id]
				const isError = account.error;

				if (account.isUnified !== true && account.visible !== false) {
					const route = {
						name: 'accountSettings',
						params: {
							accountId: account.id,
						}
					}
					items.push({
						id: 'account' + account.id,
						key: 'account' + account.id,
						text: account.emailAddress,
						bullet: isError ? undefined : calculateAccountColor(account.name), // TODO
						icon: isError ? 'icon-error' : undefined,
						router: route,
						utils: {
							actions: [
								{
									icon: 'icon-settings',
									text: t('mail', 'Edit'),
									action: () => {
										this.$router.push(route)
									},
								},
								{
									icon: 'icon-delete',
									text: t('mail', 'Delete'),
									action: () => {
										this.$store.dispatch('deleteAccount', account)
											.catch(console.error.bind(this))
									}
								}
							]
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
			}

			return items
		},
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
	}
}
