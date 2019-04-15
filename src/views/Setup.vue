<template>
	<Content app-name="mail">
		<div id="emptycontent">
			<div class="icon-mail"></div>
			<h2>{{ t('mail', 'Connect your mail account') }}</h2>
			<AccountForm :display-name="displayName" :email="email" :save="onSave" />
		</div>
	</Content>
</template>

<script>
import {Content} from 'nextcloud-vue'
import AccountForm from '../components/AccountForm'

export default {
	name: 'Setup',
	components: {
		AccountForm,
		Content,
	},
	computed: {
		displayName() {
			return $('#user-displayname').text() || ''
		},
		email() {
			return $('#user-email').text() || ''
		},
	},
	methods: {
		onSave(data) {
			return this.$store.dispatch('createAccount', data).then(account => {
				this.$router.push({
					name: 'home',
				})

				return account
			})
		},
	},
}
</script>

<style>
#emptycontent {
	margin-top: 10vh;
}
</style>
