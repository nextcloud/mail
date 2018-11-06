import conv from 'color-convert'
import md5 from 'md5'
import {translate as t} from 'nextcloud-server/dist/l10n'

const SHOW_COLLAPSED = Object.seal([
	'inbox',
	'flagged',
	'drafts',
	'sent'
]);

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
				const account = accounts[id]

				if (account.isUnified !== true && account.visible !== false) {
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

				this.$store.getters.getFolders(account.id)
					.filter(folder => !account.collapsed || SHOW_COLLAPSED.indexOf(folder.specialRole) !== -1)
					.forEach(folder => {
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
								},
								exact: false,
							},
							utils: {
								counter: folder.unread,
							}
						})
					})

				if (!account.isUnified) {
					items.push({
						id: 'collapse-' + account.id,
						key: 'collapse-' + account.id,
						text: account.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders'),
						action: () => this.$store.commit('toggleAccountCollapsed', account.id)
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
								messageUid: 'new',
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
