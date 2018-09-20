import chain from "ramda/es/chain";

export default {
	methods: {
		buildMenu () {
			let items = [];

			let accounts = this.$store.getters.getAccounts();
			for (let id in accounts) {
				let account = accounts[id];

				if (account.visible !== false) {
					items.push({
						id: 'account' + account.id,
						key: 'account' + account.id,
						text: account.name,
						bullet: account.bullet, // TODO
						router: {
							name: 'accountSettings',
							params: {
								accountId: account.id,
							}
						}
					})
				}

				let folders = this.$store.getters.getFolders(account.id);
				for (let id in folders) {
					let folder = folders[id];

					let icon = 'folder';
					if (folder.specialUse) {
						icon = folder.specialUse;
					}

					items.push({
						id: 'account' + account.id + '_' + folder.id,
						key: 'account' + account.id + '_' + folder.id,
						text: folder.name,
						icon: 'icon-' + icon,
						router: {
							name: 'folder',
							params: {
								accountId: account.id,
								folderId: folder.id,
							}
						},
						utils: {
							counter: folder.unread,
						}
					})
				}
			}

			return {
				id: 'accounts-list',
				new: {
					'id': 'mail_new_message',
					text: t('mail', 'New message'),
					icon: 'icon-add',
					action: () => {
						this.$router.push('/new');
					}
				},
				items: items,
				utils: {
					counter: 0
				}
			}
		}
	}
}
