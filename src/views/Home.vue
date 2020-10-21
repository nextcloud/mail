<template>
	<Content v-shortkey.once="['c']" app-name="mail" @shortkey.native="onNewMessage">
		<Navigation />
		<MailboxThread v-if="activeAccount" :account="activeAccount" :mailbox="activeMailbox" />
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'

import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'
import logger from '../logger'
import MailboxThread from '../components/MailboxThread'
import Navigation from '../components/Navigation'

export default {
	name: 'Home',
	components: {
		Content,
		MailboxThread,
		Navigation,
	},
	mixins: [isMobile],
	computed: {
		activeAccount() {
			return this.$store.getters.getAccount(this.activeMailbox?.accountId)
		},
		activeMailbox() {
			return this.$store.getters.getMailbox(this.$route.params.mailboxId)
		},
		menu() {
			return this.buildMenu()
		},
	},
	created() {
		const accounts = this.$store.getters.accounts

		if (this.$route.name === 'home' && accounts.length > 1) {
			// Show first account
			const firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one mailbox
			const firstMailbox = this.$store.getters.getMailboxes(firstAccount.id)[0]

			console.debug('loading first mailbox of first account', firstAccount.id, firstMailbox.databaseId)

			this.$router.replace({
				name: 'mailbox',
				params: {
					mailboxId: firstMailbox.databaseId,
				},
			})
		} else if (this.$route.name === 'home' && accounts.length === 1) {
			logger.debug('the only account we have is the unified one -> show the setup page')
			this.$router.replace({
				name: 'setup',
			})
		} else if (this.$route.name === 'mailto') {
			if (accounts.length === 0) {
				console.error('cannot handle mailto:, no accounts configured')
				return
			}

			// Show first account
			const firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one mailbox
			const firstMailbox = this.$store.getters.getMailboxes(firstAccount.id)[0]

			console.debug('loading composer with first account and mailbox', firstAccount.id, firstMailbox.id)

			this.$router.replace({
				name: 'message',
				params: {
					mailboxId: firstMailbox.databaseId,
					threadId: 'new',
				},
				query: {
					to: this.$route.query.to,
					cc: this.$route.query.cc,
					bcc: this.$route.query.bcc,
					subject: this.$route.query.subject,
					body: this.$route.query.body,
				},
			})
		}
	},
	methods: {
		onNewMessage() {
			// FIXME: assumes that we're on the 'message' route already
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'new',
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep .app-content #app-content-wrapper .app-content-details {
	margin: 0 auto;
	max-width: 900px;
	display: flex;
	flex-direction: column;
	flex: 1 1 100%;
	min-width: 0;
}
</style>
