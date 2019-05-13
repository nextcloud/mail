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
		<FolderContent v-if="activeAccount" :account="activeAccount" :folder="activeFolder" />
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'

import FolderContent from '../components/FolderContent'
import logger from '../logger'
import Navigation from '../components/Navigation'

export default {
	name: 'Home',
	components: {
		Content,
		FolderContent,
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
		const accounts = this.$store.getters.getAccounts()

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
		moveMessage(e) {
			var startAccount = e.items[0].attributes.href.baseURI.match(/message\/(\d)/)
			var targetAccount = e.droptarget.href.match(/accounts\/(\d)/)
			if (targetAccount[1] == startAccount[1]) {
				var accountId = targetAccount[1]
				var startFolderId = e.items[0].attributes.href.baseURI.match(/.+-([^-]+)-\d+$/)
				var targetFolderId = e.droptarget.href.match(/.+\/([\S]+)$/)
				if (startFolderId[1] != targetFolderId[1]) {
					var msgId = e.items[0].attributes.href.baseURI.match(/.+-([\S]+)$/)
					this.$store.dispatch('moveMessage', {
						accountId: accountId,
						startFolderId: startFolderId[1],
						targetFolderId: targetFolderId[1],
						msgId: msgId[1],
					})
				} else {
					console.info('target folder is the same as start folder => wont try to move email')
				}
			} else {
				console.info('Cannot move email in another mailbox')
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
