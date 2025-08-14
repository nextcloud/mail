<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="mail" class="mail-content">
		<Navigation />
		<Outbox v-if="$route.name === 'outbox'" />
		<MailboxThread v-else-if="activeAccount"
			:account="activeAccount"
			:mailbox="activeMailbox" />

		<template v-if="hasComposerSession && accounts !== null">
			<ComposerSessionIndicator @close="onCloseMessageModal" />
			<NewMessageModal ref="newMessageModal" :accounts="accounts" />
		</template>
	</NcContent>
</template>

<script>
import { NcContent } from '@nextcloud/vue'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile.js'

import '../../css/mail.scss'
import '../../css/mobile.scss'

import { testAccountConnection } from '../service/AccountService.js'
import logger from '../logger.js'
import MailboxThread from '../components/MailboxThread.vue'
import Navigation from '../components/Navigation.vue'
import Outbox from '../components/Outbox.vue'
import ComposerSessionIndicator from '../components/ComposerSessionIndicator.vue'
import { mapState, mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'Home',
	components: {
		NcContent,
		MailboxThread,
		Navigation,
		NewMessageModal: () => import(/* webpackChunkName: "new-message-modal" */ '../components/NewMessageModal.vue'),
		Outbox,
		ComposerSessionIndicator,
	},
	mixins: [isMobile],
	data() {
		return {
			hasComposerSession: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, ['composerSessionId']),
		accounts() {
			return this.mainStore.getAccounts.filter((a) => !a.isUnified)
		},
		activeAccount() {
			return this.mainStore.getAccount(this.activeMailbox?.accountId)
		},
		activeMailbox() {
			return this.mainStore.getMailbox(this.$route.params.mailboxId)
		},
	},
	watch: {
		async composerSessionId(id) {
			// Session was closed or discarded
			if (!id) {
				this.hasComposerSession = false
				return
			}

			// A new session is replacing the old session.  Fully reset the NewMessageModal
			// component in this case and wait for the template to fully render before showing the
			// modal again.
			if (this.hasComposerSession) {
				this.hasComposerSession = false
				await this.$nextTick()
			}

			this.hasComposerSession = true
		},
	},
	async beforeMount() {
		for (const account of this.accounts) {
			await this.mainStore.patchAccountMutation({
				account,
				data: { connectionStatus: await testAccountConnection(account.accountId) },
			})
		}
	},
	created() {
		const accounts = this.mainStore.getAccounts
		let startMailboxId = this.mainStore.getPreference('start-mailbox-id')
		if (startMailboxId && !this.mainStore.getMailbox(startMailboxId)) {
			// The start ID is set but the mailbox doesn't exist anymore
			startMailboxId = null
		}

		if (this.$route.name === 'home' && accounts.length > 1 && startMailboxId) {
			logger.debug('Loading start folder', { id: startMailboxId })
			this.$router.replace({
				name: 'mailbox',
				params: {
					mailboxId: startMailboxId,
				},
			})
		} else if (this.$route.name === 'home' && accounts.length > 1) {
			// Show first account
			const firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one mailbox
			const firstMailbox = this.mainStore.getMailboxes(firstAccount.id)[0]

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
			const firstMailbox = this.mainStore.getMailboxes(firstAccount.id)[0]

			console.debug('loading composer with first account and folder', firstAccount.id, firstMailbox.id)

			this.$router.replace({
				name: 'message',
				params: {
					mailboxId: firstMailbox.databaseId,
					threadId: 'mailto',
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
		hideMessage() {
			this.$router.replace({
				name: 'mailbox',
				params: {
					mailboxId: this.$route.params.mailboxId,
					filter: this.$route.params?.filter,
				},
			})
		},
		async onCloseMessageModal() {
			await this.$refs.newMessageModal.onClose()
		},
	},
}

</script>

<style lang="scss">
@media print {
	body {
		/*
		 * Nextcloud uses an inner scrolling but we need the
		 * full page to scroll for print
		 */
		position: relative;
		height: initial;
	}
}
</style>

<style lang="scss" scoped>
@media print {
	.mail-content {
		height: initial;
		/* needs important because of a more specific selector */
		position: relative !important;
	}
}

:deep(.app-content-details) {
	margin: 0 auto;
	display: flex;
	flex-direction: column;
	flex: 1 1 100%;
	min-width: 70%;
}
</style>
