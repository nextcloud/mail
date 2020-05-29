<template>
	<Content v-shortkey.once="['c']" app-name="mail" @shortkey.native="onNewMessage">
		<Navigation />
		<MailboxMessage v-if="activeAccount" :account="activeAccount" :folder="activeFolder" />
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'

import MailboxMessage from '../components/MailboxMessage'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'
import logger from '../logger'
import Navigation from '../components/Navigation'

export default {
	name: 'Home',
	components: {
		Content,
		MailboxMessage,
		Navigation,
	},
	mixins: [isMobile],
	computed: {
		activeAccount() {
			return this.$store.getters.getAccount(this.$route.params.accountId)
		},
		activeFolder() {
			return this.$store.getters.getFolder(this.$route.params.accountId, this.$route.params.folderId)
		},
		menu() {
			return this.buildMenu()
		},
	},
	watch: {
		$route(to, from) {
			if (
				from.name === 'message' &&
				to.name === 'folder' &&
				!this.isMobile &&
				Number.parseInt(from.params.accountId, 10) === Number.parseInt(to.params.accountId, 10) &&
				from.params.folderId === to.params.folderId &&
				from.params.filter === to.params.filter
			) {
				logger.warn("navigation from a message to just the folder. we don't want that, do we? let's go back", {
					to,
					from,
				})
				this.$router.replace(from)
			}
		},
	},
	created() {
		const accounts = this.$store.getters.accounts

		if (this.$route.name === 'home' && accounts.length > 1) {
			// Show first account
			let firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one folder
			let firstFolder = this.$store.getters.getFolders(firstAccount.id)[0]

			console.debug('loading first folder of first account', firstAccount.id, firstFolder.id)

			this.$router.replace({
				name: 'folder',
				params: {
					accountId: firstAccount.id,
					folderId: firstFolder.id,
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
			let firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one folder
			let firstFolder = this.$store.getters.getFolders(firstAccount.id)[0]

			console.debug('loading composer with first account and folder', firstAccount.id, firstFolder.id)

			this.$router.replace({
				name: 'message',
				params: {
					accountId: firstAccount.id,
					folderId: firstFolder.id,
					messageUid: 'new',
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
					accountId: this.$route.params.accountId,
					folderId: this.$route.params.folderId,
					messageUid: 'new',
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep #app-content #app-content-wrapper .app-content-details {
	margin: 0 auto;
	max-width: 900px;
	display: flex;
	flex-direction: column;
}
</style>
