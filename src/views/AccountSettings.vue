<template>
	<AppContent app-name="mail">
		<template slot="navigation">
			<AppNavigationNew :text="t('mail', 'New message')"
							  buttonId="mail_new_message"
							  buttonClass="icon-add"
							  @click="onNewMessage"/>
			<ul id="accounts-list">
				<AppNavigationItem v-for="item in menu"
								   :key="item.key"
								   :item="item"/>
			</ul>
			<AppNavigationSettings :title="t('mail', 'Settings')">
				<AppSettingsMenu/>
			</AppNavigationSettings>
		</template>
		<template slot="content">
			<div class="section" id="account-info">
				<h2>{{ t ('mail', 'Account Settings') }} - {{ email }}</h2>
			</div>
			<div class="section">
				<div id="mail-settings">
					<AccountForm
							:displayName="displayName"
							:email="email"
							:save="onSave"
							:settingsPage="true"
					/>
				</div>
			</div>
		</template>
	</AppContent>
</template>

<script>
	import {
		AppContent,
		AppNavigationItem,
		AppNavigationNew,
		AppNavigationSettings
	} from 'nextcloud-vue'
	import AppSettingsMenu from '../components/AppSettingsMenu'
	import AccountForm from '../components/AccountForm'

	import SidebarItems from '../mixins/SidebarItems'

	export default {
		name: 'AccountSettings',
		components: {
			AccountForm,
			AppContent,
			AppNavigationItem,
			AppNavigationNew,
			AppNavigationSettings,
			AppSettingsMenu,
		},
		extends: SidebarItems,
		computed: {
			menu () {
				return this.buildMenu()
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
					.then(account => {
						console.log('updated account. Got response in onSave')

						return account
					})
			},
		},
	}
</script>
