<template>
	<Content app-name="mail">
		<Navigation v-if="hasAccounts" />
		<div class="mail-empty-content">
			<EmptyContent icon="icon-mail">
				<h2>{{ t('mail', 'Connect your mail account') }}</h2>
				<template #desc>
					<AccountForm :display-name="displayName" :email="email" :save="onSave">
						<template v-if="error" #feedback class="warning">
							{{ error }}
						</template>
					</AccountForm>
				</template>
			</EmptyContent>
		</div>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'

import AccountForm from '../components/AccountForm'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import Navigation from '../components/Navigation'
import logger from '../logger'

export default {
	name: 'Setup',
	components: {
		AccountForm,
		Content,
		EmptyContent,
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

					if (error.data?.error === 'AUTOCONFIG_FAILED') {
						this.error = t('mail', 'Auto detect failed. Please try manual mode.')
					} else if (error.data?.error === 'CONNECTION_ERROR') {
						if (error.data.service === 'IMAP') {
							this.error = t('mail', 'Manual config failed. IMAP server is not reachable.')
						} else if (error.data.service === 'SMTP') {
							this.error = t('mail', 'Manual config failed. SMTP server is not reachable.')
						}
					} else if (error.data?.error === 'AUTHENTICATION') {
						if (error.data.service === 'IMAP') {
							this.error = t('mail', 'Manual config failed. IMAP username or password is wrong.')
						} else if (error.data.service === 'SMTP') {
							this.error = t('mail', 'Manual config failed. SMTP username or password is wrong.')
						}
					} else {
						this.error = t('mail', 'There was an error while setting up your account. Please try again.')
					}
				})
		},
	},
}
</script>

<style>
.mail-empty-content {
	margin: 0 auto;
}
</style>
