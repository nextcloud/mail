<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="default-folders">
		<MailboxInlinePicker
			:label="t('mail', 'Drafts are saved in:')"
			:value="account.draftsMailboxId"
			:account="account"
			:disabled="saving['draftsMailboxId']"
			@update="(folderId) => updateFolder('draftsMailboxId', folderId)" />

		<MailboxInlinePicker
			:label="t('mail', 'Sent messages are saved in:')"
			:value="account.sentMailboxId"
			:account="account"
			:disabled="saving['sentMailboxId']"
			@update="(folderId) => updateFolder('sentMailboxId', folderId)" />

		<MailboxInlinePicker
			:label="t('mail', 'Deleted messages are moved in:')"
			:value="account.trashMailboxId"
			:account="account"
			:disabled="saving['trashMailboxId']"
			@update="(folderId) => updateFolder('trashMailboxId', folderId)" />

		<MailboxInlinePicker
			:label="t('mail', 'Archived messages are moved in:')"
			:value="account.archiveMailboxId"
			:account="account"
			:disabled="saving['archiveMailboxId']"
			@update="(folderId) => updateFolder('archiveMailboxId', folderId)" />

		<MailboxInlinePicker
			:label="t('mail', 'Snoozed messages are moved in:')"
			:value="account.snoozeMailboxId"
			:account="account"
			:disabled="saving['snoozeMailboxId']"
			@update="(folderId) => updateFolder('snoozeMailboxId', folderId)" />

		<MailboxInlinePicker
			:label="t('mail', 'Junk messages are saved in:')"
			:value="account.junkMailboxId"
			:account="account"
			:disabled="saving['junkMailboxId']"
			@update="(folderId) => updateFolder('junkMailboxId', folderId)" />
	</div>
</template>

<script>
import MailboxInlinePicker from './MailboxInlinePicker.vue'
import logger from '../logger.js'
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
			saving: {
				draftsMailboxId: false,
				sentMailboxId: false,
				trashMailboxId: false,
				archiveMailboxId: false,
				snoozeMailboxId: false,
				junkMailboxId: false,
			},
		}
	},

	computed: {
		mainStore() {
			return useMainStore()
		},
	},

	methods: {
		async updateFolder(type, id) {
			this.saving[type] = true
			try {
				await this.mainStore.patchAccount({
					account: this.account,
					data: {
						[type]: id,
					},
				})
			} catch (error) {
				logger.error('could not set default folder', {
					error,
				})
			} finally {
				this.saving[type] = false
			}
		},
	},
}
</script>

<style>
.default-folders {
	display: flex;
	flex-direction: column;
}
</style>
