<template>
  <div id="content" class="mail">
    <app-navigation :menu="menu">
      <AppSettingsMenu slot="settings-content"/>
    </app-navigation>
    <div id="app-content">
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
    </div>
  </div>
</template>

<script>
import { AppNavigation } from 'nextcloud-vue'
import AppSettingsMenu from '../components/AppSettingsMenu'
import AccountForm from '../components/AccountForm'

import SidebarItems from '../mixins/SidebarItems'

export default {
	name: 'AccountSettings',
	components: {
		AccountForm,
		AppNavigation,
		AppSettingsMenu,
	},
	extends: SidebarItems,
	computed: {
		menu() {
			return this.buildMenu()
		},
		displayName() {
			return this.$store.getters.getAccount(this.$route.params.accountId)
				.name
		},
		email() {
			return this.$store.getters.getAccount(this.$route.params.accountId)
				.emailAddress
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
				.then(account => {
					console.log('updated account. Got response in onSave')

					return account
				})
		},
	},
}
</script>
