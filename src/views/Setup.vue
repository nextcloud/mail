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
import Content from '@nextcloud/vue/dist/Components/Content'
import {loadState} from '@nextcloud/initial-state'

import AccountForm from '../components/AccountForm'

export default {
	name: 'Setup',
	components: {
		AccountForm,
		Content,
	},
	data() {
		return {
			displayName: loadState('mail', 'prefill_displayName'),
			email: loadState('mail', 'prefill_email'),
		}
	},
	methods: {
		onSave(data) {
			return this.$store
				.dispatch('createAccount', data)
				.then(account => {
					this.$router.push({
						name: 'home',
					})

					return account
				})
				.catch(e => {
					console.error(e)
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
