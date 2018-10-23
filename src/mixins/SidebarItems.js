import conv from 'color-convert'
import md5 from 'md5'

export default {
	methods: {
		accountBulletColor (name) {
			const hashed = md5(name)
			const hsl = conv.hex.hsl(hashed)
			const fixedHsl = [Math.round(hsl[0] / 40) * 40, hsl[1], hsl[2]]
			return '#' + conv.hsl.hex(fixedHsl)
		},

		buildMenu () {
			let items = [];

			let accounts = this.$store.getters.getAccounts();
			for (let id in accounts) {
				let account = accounts[id];

				if (account.visible !== false) {
					items.push({
						id: 'account' + account.id,
						key: 'account' + account.id,
						text: account.emailAddress,
						bullet: this.accountBulletColor(account.name), // TODO
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
					if (folder.specialRole) {
						icon = folder.specialRole;
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
						// FIXME: assumes that we're on the 'message' route already
						this.$router.push({
							name: 'message',
							params: {
								accountId: this.$route.params.accountId,
								folderId: this.$route.params.folderId,
								messageId: 'new',
							}
						});
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
