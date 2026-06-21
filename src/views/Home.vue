<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="mail" class="mail-content">
		<Navigation />
		<Outbox v-if="$route.name === 'outbox'" />
		<MailboxThread
			v-else-if="activeAccount"
			:account="activeAccount"
			:mailbox="activeMailbox" />

		<template v-if="hasComposerSession && accounts !== null">
			<ComposerSessionIndicator @close="onCloseMessageModal" />
			<NewMessageModal ref="newMessageModal" :accounts="accounts" />
		</template>

		<!-- Rendered here, at the stable app root, rather than inside the
		     navigation: NcModal relocates the dialog to <body> on mount, but the
		     frequently re-rendering navigation moves it back into the scrollable,
		     overflow-clipped sidebar. CKEditor then considers its content clipped
		     and parks every balloon (image toolbar, link form) off-screen. -->
		<AccountSettings
			v-if="settingsAccount"
			:open="settingsOpen"
			:account="settingsAccount"
			:scroll-to-section="settingsSection"
			@update:open="onCloseAccountSettings" />
	</NcContent>
</template>

<script>
import { NcContent } from '@nextcloud/vue'
import { mapState, mapStores } from 'pinia'
import ComposerSessionIndicator from '../components/ComposerSessionIndicator.vue'
import MailboxThread from '../components/MailboxThread.vue'
import Navigation from '../components/Navigation.vue'
import Outbox from '../components/Outbox.vue'
import logger from '../logger.js'
import { testAccountConnection } from '../service/AccountService.js'
import useMainStore from '../store/mainStore.js'

import '../../css/mail.scss'

export default {
	name: 'Home',
	components: {
		NcContent,
		MailboxThread,
		Navigation,
		NewMessageModal: () => import(/* webpackChunkName: "new-message-modal" */ '../components/NewMessageModal.vue'),
		AccountSettings: () => import(/* webpackChunkName: "account-settings" */ '../components/AccountSettings.vue'),
		Outbox,
		ComposerSessionIndicator,
	},

	data() {
		return {
			hasComposerSession: false,
			// Id of the account whose settings dialog is open. The id is kept after
			// closing so the dialog stays mounted for its out-transition. The account
			// object itself is resolved reactively (see settingsAccount) so settings
			// sub-components observe store updates live instead of a stale snapshot.
			settingsAccountId: null,
			settingsOpen: false,
			settingsSection: undefined,
		}
	},

	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, ['composerSessionId', 'showAccountSettings']),
		accounts() {
			return this.mainStore.getAccounts.filter((a) => !a.isUnified)
		},

		activeAccount() {
			return this.mainStore.getAccount(this.activeMailbox?.accountId)
		},

		activeMailbox() {
			return this.mainStore.getMailbox(this.$route.params.mailboxId)
		},

		settingsAccount() {
			return this.settingsAccountId !== null
				? this.mainStore.getAccount(this.settingsAccountId)
				: null
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

		showAccountSettings: {
			immediate: true,
			handler(settings) {
				if (settings?.accountId) {
					this.settingsAccountId = settings.accountId
					this.settingsSection = settings.section
					// Mount first (settingsAccount), then open on the next tick so the
					// dialog's `open` watcher fires and runs its on-open side effects.
					this.$nextTick(() => {
						this.settingsOpen = true
					})
				} else {
					this.settingsOpen = false
				}
			},
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

			logger.debug('loading first mailbox of first account', { accountId: firstAccount.id, mailboxId: firstMailbox.databaseId })

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
				logger.error('cannot handle mailto:, no accounts configured')
				return
			}

			// Show first account
			const firstAccount = accounts[0]
			// FIXME: this assumes that there's at least one mailbox
			const firstMailbox = this.mainStore.getMailboxes(firstAccount.id)[0]

			logger.debug('loading composer with first account and folder', { accountId: firstAccount.id, mailboxId: firstMailbox.id })

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

		onCloseAccountSettings() {
			this.mainStore.showSettingsForAccountMutation(null)
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
