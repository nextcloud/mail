<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<p>
			{{ t('mail', 'Drafts are saved in:') }}
		</p>
		<MailboxInlinePicker v-model="draftsMailbox" :account="account" :disabled="saving" />

		<p>
			{{ t('mail', 'Sent messages are saved in:') }}
		</p>

		<MailboxInlinePicker v-model="sentMailbox" :account="account" :disabled="saving" />
		<p>
			{{ t('mail', 'Deleted messages are moved in:') }}
		</p>

		<MailboxInlinePicker v-model="trashMailbox" :account="account" :disabled="saving" />
		<p>
			{{ t('mail', 'Archived messages are moved in:') }}
		</p>

		<MailboxInlinePicker v-model="archiveMailbox" :account="account" :disabled="saving" />
		<p>
			{{ t('mail', 'Snoozed messages are moved in:') }}
		</p>

		<MailboxInlinePicker v-model="snoozeMailbox" :account="account" :disabled="saving" />

		<p>
			{{ t('mail', 'Junk messages are saved in:') }}
		</p>
		<MailboxInlinePicker v-model="junkMailbox" :account="account" :disabled="saving" />
	</div>
</template>

<script>
import logger from '../logger.js'
import MailboxInlinePicker from './MailboxInlinePicker.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'AccountDefaultsSettings',
	components: {
		MailboxInlinePicker,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			saving: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		draftsMailbox: {
			get() {
				const mb = this.mainStore.getMailbox(this.account.draftsMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(draftsMailboxId) {
				logger.debug('setting drafts folder to ' + draftsMailboxId)
				this.saving = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							draftsMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set drafts folder', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		sentMailbox: {
			get() {
				const mb = this.mainStore.getMailbox(this.account.sentMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(sentMailboxId) {
				logger.debug('setting sent folder to ' + sentMailboxId)
				this.saving = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							sentMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set sent folder', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		trashMailbox: {
			get() {
				const mb = this.mainStore.getMailbox(this.account.trashMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(trashMailboxId) {
				logger.debug('setting trash folder to ' + trashMailboxId)
				this.saving = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							trashMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set trash folder', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		archiveMailbox: {
			get() {
				const mb = this.mainStore.getMailbox(this.account.archiveMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(archiveMailboxId) {
				logger.debug('setting archive folder to ' + archiveMailboxId)
				this.saving = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							archiveMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set archive folder', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		junkMailbox: {
			get() {
				const mb = this.mainStore.getMailbox(this.account.junkMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(junkMailboxId) {
				logger.debug('setting junk folder to ' + junkMailboxId)
				this.saving = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							junkMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set junk folder', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
		snoozeMailbox: {
			get() {
				const mb = this.mainStore.getMailbox(this.account.snoozeMailboxId)
				if (!mb) {
					return
				}
				return mb.databaseId
			},
			async set(snoozeMailboxId) {
				logger.debug('setting snooze folder to ' + snoozeMailboxId)
				this.saving = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							snoozeMailboxId,
						},
					})
				} catch (error) {
					logger.error('could not set snooze folder', {
						error,
					})
				} finally {
					this.saving = false
				}
			},
		},
	},
}
</script>

<style lang="scss" scoped>
.button.icon-rename {
	background-color: transparent;
	border: none;
	opacity: 0.3;

	&:hover,
	&:focus {
		opacity: 1;
	}
}
</style>
