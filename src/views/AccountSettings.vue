<template>
	<Content app-name="mail">
		<Navigation />
		<AppContent>
			<div class="section">
				<FilterSettings />
			</div>
			<div class="section">
				<h2>{{ t('mail', 'Account Settings') }} - {{ email }}</h2>
				<h3>{{ t('mail', 'Mail server') }}</h3>
				<div id="mail-settings">
					<AccountForm :display-name="displayName" :email="email" :save="onSave" :account="account" />
				</div>
				<EditorSettings :account="account" />
				<SignatureSettings :account="account" />
			</div>
		</AppContent>
	</Content>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import Content from '@nextcloud/vue/dist/Components/Content'

import AccountForm from '../components/AccountForm'
import EditorSettings from '../components/EditorSettings'
import Logger from '../logger'
import Navigation from '../components/Navigation'
import SignatureSettings from '../components/SignatureSettings'
import FilterSettings from '../components/FilterSettings'

export default {
	name: 'AccountSettings',
	components: {
		AccountForm,
		AppContent,
		Content,
		EditorSettings,
		Navigation,
		SignatureSettings,
		FilterSettings,
	},
	data() {
		const account = this.$store.getters.getAccount(this.$route.params.accountId)
		return {
			account,
			signature: account.signature,
		}
	},
	computed: {
		menu() {
			return this.buildMenu()
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
			Logger.log('saving data', {data})
			return this.$store
				.dispatch('updateAccount', {
					...data,
					accountId: this.$route.params.accountId,
				})
				.then(account => account)
				.catch(error => {
					Logger.error('account update failed:', {error})

					throw error
				})
		},
	},
}
</script>
