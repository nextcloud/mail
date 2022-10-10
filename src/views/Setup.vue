<template>
	<Content app-name="mail">
		<Navigation v-if="hasAccounts" />
		<AppContent>
			<div class="mail-empty-content">
				<EmptyContent v-if="allowNewMailAccounts" :title="t('mail', 'Connect your mail account')">
					<template #icon>
						<IconMail :size="65" />
					</template>
					<template #action>
						<AccountForm :display-name="displayName"
							:email="email"
							:error.sync="error"
							@account-created="onAccountCreated" />
					</template>
				</EmptyContent>
				<EmptyContent v-else :title="t('mail', 'To add a mail account, please contact your administrator.')">
					<template #icon>
						<IconMail :size="65" />
					</template>
				</EmptyContent>
			</div>
		</AppContent>
	</Content>
</template>

<script>
import { NcContent as Content, NcAppContent as AppContent, NcEmptyContent as EmptyContent } from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'

import AccountForm from '../components/AccountForm'
import IconMail from 'vue-material-design-icons/Email'
import Navigation from '../components/Navigation'
import logger from '../logger'

export default {
	name: 'Setup',
	components: {
		AppContent,
		AccountForm,
		Content,
		EmptyContent,
		IconMail,
		Navigation,
	},
	data() {
		return {
			displayName: loadState('mail', 'prefill_displayName'),
			email: loadState('mail', 'prefill_email'),
			allowNewMailAccounts: loadState('mail', 'allow-new-accounts', true),
			error: null,
		}
	},
	computed: {
		hasAccounts() {
			return this.$store.getters.accounts.length > 1
		},
	},
	methods: {
		onAccountCreated() {
			logger.info('account successfully created, redirecting …')
			this.$router.push({
				name: 'home',
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
