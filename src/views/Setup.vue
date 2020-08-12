<template>
	<Content app-name="mail">
		<Navigation v-if="hasAccounts" />
		<div id="emptycontent">
			<div class="icon-mail" />
			<h2>{{ t('mail', 'Connect your mail account') }}</h2>
			<AccountForm :display-name="displayName" :email="email" :save="onSave">
				<template v-if="error" #feedback class="warning">
					{{ error }}
				</template>
			</AccountForm>
		</div>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'

import AccountForm from '../components/AccountForm'
import Navigation from '../components/Navigation'
import logger from '../logger'

export default {
	name: 'Setup',
	components: {
		AccountForm,
		Content,
		Navigation,
	},
	data() {
		return {
			displayName: loadState('mail', 'prefill_displayName'),
			email: loadState('mail', 'prefill_email'),
			error: null,
		}
	},
	computed: {
		hasAccounts() {
			return this.$store.getters.accounts.length > 1
		},
	},
	methods: {
		onSave(data) {
			this.error = null

			return this.$store
				.dispatch('createAccount', data)
				.then((account) => {
					logger.info('account successfully created, redirecting …')
					this.$router.push({
						name: 'home',
					})

					return account
				})
				.catch((error) => {
					logger.error('Could not create account', { error })

					if (error.message) {
						this.error = error.message
					} else {
						this.error = t('mail', 'Unexpected error during account creation')
					}
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
