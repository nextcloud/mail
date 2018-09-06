import chain from "ramda/es/chain";

export default {
	methods: {
		buildMenu (accounts) {
			const items = chain(account => {
				let items = []

				if (account.visible !== false) {
					items.push({
						id: 'account' + account.id,
						key: 'account' + account.id,
						text: account.name,
						bullet: account.bullet // TODO
					})
				}

				return items.concat(account.folders.map(folder => {
					let icon = 'folder';
					if (folder.specialUse) {
						icon = folder.specialUse;
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
							}
						},
						utils: {
							counter: folder.unread,
						}
					}
				}))
			}, accounts);

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
