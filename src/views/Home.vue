<template>
	<Content
		v-shortkey.once="['c']"
		v-drag-and-drop:options="{
			dropzoneSelector: '.icon-folder',
			draggableSelector: '.app-content-list-item',
			onDrop: function(e) {
				moveMessage(e)
			},
		}"
		app-name="mail"
		@shortkey.native="onNewMessage"
	>
		<Navigation />
		<MailboxMessage v-if="activeAccount" :account="activeAccount" :folder="activeFolder" />
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'

import MailboxMessage from '../components/MailboxMessage'
import logger from '../logger'
import Navigation from '../components/Navigation'

export default {
	name: 'Home',
	components: {
		Content,
		MailboxMessage,
		Navigation,
	},
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
	created() {
		const accounts = this.$store.getters.accounts

		if (this.$route.name === 'home' && accounts.length > 1) {
			// Show first account
			let firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one folder
			let firstFolder = this.$store.getters.getFolders(firstAccount.id)[0]

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
				Logger.error('cannot handle mailto:, no accounts configured')
				return
			}

			// Show first account
			let firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one folder
			let firstFolder = this.$store.getters.getFolders(firstAccount.id)[0]

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
		moveMessage(e) {

			// We don't want to use this.activeAccount here because we want it to work in the unified inbox
			var startAccount = e.items[0].attributes.href.baseURI.match(/message\/(\d)/)
			var targetAccount = e.droptarget.href.match(/accounts\/(\d)/)

			if (targetAccount[1] == startAccount[1]) {
				var accountId = targetAccount[1]

				// We don't want to use this.activeFolder here because we want it to work in the unified inbox
				var startFolderId = e.items[0].attributes.href.baseURI.match(/.+-([^-]+)-\d+$/)
				var targetFolderId = e.droptarget.href.match(/.+\/([\S]+)$/)

				if (startFolderId[1] != targetFolderId[1]) {
					var msgId = e.items[0].attributes.href.baseURI.match(/.+-([\S]+)$/)

					// Find out next/previous envelope
					let envelopes = this.$store.getters.getEnvelopes(this.$route.params.accountId, this.$route.params.folderId)
					let currentEnv = this.$store.getters.getEnvelope(startAccount[1], startFolderId[1], msgId[1])
					let next
					const idx = envelopes.indexOf(currentEnv)
					if (idx === -1) {
					        Logger.debug('envelope to delete does not exist in envelope list')
					        return
					} else if (idx === 0) {
					        next = envelopes[idx + 1]
					} else {
					        next = envelopes[idx - 1]
					}

					// Move message and navigate to next/previous envelope
					this.$store.dispatch('moveMessage', {
						accountId: accountId,
						startFolderId: startFolderId[1],
						targetFolderId: targetFolderId[1],
						msgId: msgId[1],
					}).then(() => {

						if (!next) {
						        Logger.debug('no next/previous envelope, not navigating')
						        return
						}

						// Keep the selected account-folder combination, but navigate to a different message
						// (it's not a bug that we don't use next.accountId and next.folderId here)
						this.$router.push({
						        name: 'message',
						        params: {
						                accountId: this.$route.params.accountId,
						                folderId: this.$route.params.folderId,
						                messageUid: next.uid,
						        },
						})
					})
				} else {
					Logger.info('target folder is the same as start folder => wont try to move email')
				}
			} else {
				Logger.info('Cannot move email in another mailbox')
			}
		},
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
}
</style>
