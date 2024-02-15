<template>
	<NcContent app-name="mail">
		<Navigation v-if="hasAccounts" />
		<AppContent class="container">
			<div v-if="allowNewMailAccounts">
				<EmptyContent :name="t('mail', 'Connect your mail account')">
					<template #icon>
						<IconMail :size="65" />
					</template>
				</EmptyContent>
				<AccountForm :display-name="displayName"
					:email="email"
					:error.sync="error"
					@account-created="onAccountCreated" />
			</div>
			<EmptyContent v-else :name="t('mail', 'To add a mail account, please contact your administrator.')">
				<template #icon>
					<IconMail :size="65" />
				</template>
			</EmptyContent>
		</AppContent>
	</NcContent>
</template>

<script>
import { NcContent, NcAppContent as AppContent, NcEmptyContent as EmptyContent } from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'

import AccountForm from '../components/AccountForm.vue'
import IconMail from 'vue-material-design-icons/Email.vue'
import Navigation from '../components/Navigation.vue'
import logger from '../logger.js'

export default {
	name: 'Setup',
	components: {
		AppContent,
		AccountForm,
		NcContent,
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
.container{
 display: flex;
 justify-content: center;
}
</style>
