<template>
	<Content app-name="mail">
		<Navigation />
		<AppContent>
			<div class="section">
				<h2>{{ t('mail', 'Account settings') }}</h2>
				<p>
					<strong>{{ displayName }}</strong> &lt;{{ email }}&gt;
					<a
						v-if="!account.provisioned"
						class="button icon-rename"
						href="#account-form"
						:title="t('mail', 'Change name')"
					></a>
				</p>
			</div>
			<SignatureSettings :account="account" />
			<EditorSettings :account="account" />
			<div v-if="!account.provisioned" class="section">
				<h2>{{ t('mail', 'Mail server') }}</h2>
				<div id="mail-settings">
					<AccountForm
						:key="account.accountId"
						:display-name="displayName"
						:email="email"
						:save="onSave"
						:account="account"
					/>
				</div>
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

export default {
	name: 'AccountSettings',
	components: {
		AccountForm,
		AppContent,
		Content,
		EditorSettings,
		Navigation,
		SignatureSettings,
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
				.then((account) => account)
				.catch((error) => {
					Logger.error('account update failed:', {error})

					throw error
				})
		},
	},
}
</script>

<style lang="scss" scoped>
.button.icon-rename {
	background-color: transparent;
	border: none;
	opacity: 0.3;

	&:hover,
	&:focus {
		opacity: 1;
	}
}
</style>
