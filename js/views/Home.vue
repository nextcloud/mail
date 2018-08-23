<template>
	<div id="content" class="mail">
		<app-navigation :menu="menu"/>
		<FolderContent />
	</div>
</template>

<script>
	import chain from "ramda/es/chain";

	import AppNavigation from "../components/core/appNavigation";
	import FolderContent from "../components/FolderContent";

	export default {
		name: 'home',
		components: {
			AppNavigation,
			FolderContent,
		},
		computed: {
			menu () {
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
						var icon = 'folder';
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
				}, this.$store.state.accounts);

				return {
					id: 'accounts-list',
					new: {
						'id': 'mail_new_message',
						text: t('mail', 'New message'),
						icon: 'icon-add',
						action: this.newMessage
					},
					items: items,
					utils: {
						counter: 0
					}
				}
			}
		},
		methods: {
			newMessage () {
				console.info('New message clicked');
			}
		}
	}
</script>
