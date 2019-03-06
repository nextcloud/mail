<template>
	<AppContent app-name="mail">
		<Navigation slot="navigation"/>
		<template slot="content">
			<div class="section" id="account-info">
				<h2>{{ t ('mail', 'Account Settings') }} - {{ email }}</h2>
			</div>
			<div class="section">
				<div id="mail-settings">
					<AccountForm :displayName="displayName"
								 :email="email"
								 :save="onSave"
								 :account="account"/>
				</div>
			</div>
		</template>
	</AppContent>
</template>

<script>
	import {AppContent} from 'nextcloud-vue'

	import AccountForm from '../components/AccountForm'
	import Navigation from '../components/Navigation'

	export default {
		name: 'AccountSettings',
		components: {
			AccountForm,
			AppContent,
			Navigation,
		},
		extends: SidebarItems,
		computed: {
			menu () {
				return this.buildMenu()
			},
			account () {
				return this.$store.getters.getAccount(this.$route.params.accountId)
			},
			displayName () {
				return this.$store.getters.getAccount(this.$route.params.accountId)
					.name
			},
			email () {
				return this.$store.getters.getAccount(this.$route.params.accountId)
					.emailAddress
			},
		},
		methods: {
			onSave (data) {
				console.log('data to save:', data)
				return this.$store
					.dispatch('updateAccount', {
						...data,
						accountId: this.$route.params.accountId,
					})
					.then(account => account)
					.catch(err => {
						console.error('account update failed:', err)

						throw err
					})
			},
		},
	}
</script>
