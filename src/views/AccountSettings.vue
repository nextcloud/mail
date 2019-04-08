<template>
	<Content app-name="mail">
		<Navigation slot="navigation" />
		<AppContent slot="content">
			<div class="section">
				<h2>{{ t('mail', 'Account Settings') }} - {{ email }}</h2>
				<div id="mail-settings">
					<AccountForm :display-name="displayName" :email="email" :save="onSave" :account="account" />
				</div>
			</div>
		</AppContent>
	</Content>
</template>

<script>
import {AppContent, Content} from 'nextcloud-vue'

import AccountForm from '../components/AccountForm'
import Navigation from '../components/Navigation'

export default {
	name: 'AccountSettings',
	components: {
		AccountForm,
		AppContent,
		Content,
		Navigation,
	},
	computed: {
		menu() {
			return this.buildMenu()
		},
		account() {
			return this.$store.getters.getAccount(this.$route.params.accountId)
		},
		displayName() {
			return this.$store.getters.getAccount(this.$route.params.accountId).name
		},
		email() {
			return this.$store.getters.getAccount(this.$route.params.accountId).emailAddress
		},
	},
	methods: {
		onSave(data) {
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
