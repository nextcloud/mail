<template>
	<div id="content"
		 class="app-mail"
		 v-shortkey.once="['c']"
		 @shortkey="onNewMessage">
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
	import {AppNavigation} from 'nextcloud-vue'
	import AppSettingsMenu from '../components/AppSettingsMenu'
	import FolderContent from '../components/FolderContent'
	import Loading from '../components/Loading'

	import SidebarItems from '../mixins/SidebarItems'

	export default {
		name: 'Home',
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
				return this.buildMenu()
			}
		},
		created () {
			const accounts = this.$store.getters.getAccounts()

			return Promise.all(accounts.map(account => {
				return this.$store.dispatch('fetchFolders', {
					accountId: account.id,
				})
			})).then(() => {
				this.loading = false

				console.debug('account folders fetched', accounts)

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
				} else if (this.$route.name === 'mailto') {
					if (accounts.length === 0) {
						console.error('cannot handle mailto:, no accounts configured')
						return
					}

					// Show first account
					let firstAccount = accounts[0]
					// FIXME: this assumes that there's at least one folder
					let firstFolder = this.$store.getters.getFolders(firstAccount.id)[0]

					console.debug('loading composer with first account and folder', firstAccount.id, firstFolder.id)

					this.$router.replace({
						name: 'message',
						params: {
							accountId: firstAccount.id,
							folderId: firstFolder.id,
							messageUid: 'new',
						},
						query: {
							to: this.$route.query.to,
							cc: this.$route.query.cc,
							bcc: this.$route.query.bcc,
							subject: this.$route.query.subject,
							body: this.$route.query.body,
						}
					})
				}
			}).catch(console.error.bind(this))
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
		}
	}
</script>
