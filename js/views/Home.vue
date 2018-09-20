<template>
	<div id="content" class="mail">
		<Loading v-if="loading" :hint="t('mail', 'Loading your accounts')"/>
		<template v-else>
			<app-navigation :menu="menu">
				<AppSettingsMenu slot="settings-content"/>
			</app-navigation>
			<FolderContent/>
		</template>
	</div>
</template>

<script>
	import AppNavigation from "../components/core/appNavigation";
	import AppSettingsMenu from "../components/AppSettingsMenu";
	import FolderContent from "../components/FolderContent";
	import Loading from "../components/Loading";

	import SidebarItems from "../mixins/SidebarItems";

	export default {
		name: 'home',
		extends: SidebarItems,
		components: {
			Loading,
			AppNavigation,
			AppSettingsMenu,
			FolderContent,
		},
		data () {
			return {
				loading: true
			}
		},
		computed: {
			menu () {
				return this.buildMenu();
			}
		},
		created () {
			this.$store.dispatch('fetchAccounts')
				.then(accounts => {
					return Promise.all(accounts.map(account => {
						return this.$store.dispatch('fetchFolders', {
							accountId: account.id,
						})
					})).then(() => {
						return accounts
					})
				}).then(accounts => {
				this.loading = false

				console.debug('accounts fetched', accounts)

				if (this.$route.name === 'home' && accounts.length > 0) {
					// Show first account

					let firstAccount = accounts[0]
					// FIXME: this assumes that there's at least one folder
					let firstFolder = this.$store.getters.getFolders(firstAccount.id)[0]

					console.debug('loading first folder of first account', firstAccount.id, firstFolder.id)

					this.$router.replace({
						name: 'folder',
						params: {
							accountId: firstAccount.id,
							folderId: firstFolder.id,
						}
					})
				}
			}).catch(console.error.bind(this))
		}
	};
</script>
