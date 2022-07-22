<template>
	<Content app-name="mail">
		<Navigation v-if="hasAccounts" />
		<div class="mail-empty-content">
			<EmptyContent>
				<template #icon>
					<IconMail :size="65" />
				</template>
				<h2>{{ t('mail', 'Connect your mail account') }}</h2>
				<template #desc>
					<AccountForm :display-name="displayName"
						:email="email"
						:error.sync="error"
						@account-created="onAccountCreated" />
				</template>
			</EmptyContent>
		</div>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import { loadState } from '@nextcloud/initial-state'

import AccountForm from '../components/AccountForm'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import IconMail from 'vue-material-design-icons/Email'
import Navigation from '../components/Navigation'
import logger from '../logger'

export default {
	name: 'Setup',
	components: {
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
