<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<p>
			{{ t('mail', 'Drafts are saved in:') }}
		</p>
		<MailboxInlinePicker
			:modelValue="account.draftsMailboxId"
			:account="account"
			:disabled="saving.draftsMailboxId"
			@update:modelValue="(folderId) => updateFolder('draftsMailboxId', folderId)" />

		<p>
			{{ t('mail', 'Sent messages are saved in:') }}
		</p>
		<MailboxInlinePicker
			:modelValue="account.sentMailboxId"
			:account="account"
			:disabled="saving.sentMailboxId"
			@update:modelValue="(folderId) => updateFolder('sentMailboxId', folderId)" />

		<NcCheckboxRadioSwitch
			v-if="account.protocol !== 'jmap' && !account.provisioningId"
			v-model="saveSentCopy"
			:disabled="savingSaveSentCopy">
			{{ t('mail', 'Save a copy of sent messages') }}
		</NcCheckboxRadioSwitch>

		<p>
			{{ t('mail', 'Deleted messages are moved in:') }}
		</p>
		<MailboxInlinePicker
			:modelValue="account.trashMailboxId"
			:account="account"
			:disabled="saving.trashMailboxId"
			@update:modelValue="(folderId) => updateFolder('trashMailboxId', folderId)" />

		<p>
			{{ t('mail', 'Archived messages are moved in:') }}
		</p>
		<MailboxInlinePicker
			:modelValue="account.archiveMailboxId"
			:account="account"
			:disabled="saving.archiveMailboxId"
			@update:modelValue="(folderId) => updateFolder('archiveMailboxId', folderId)" />

		<p>
			{{ t('mail', 'Snoozed messages are moved in:') }}
		</p>
		<MailboxInlinePicker
			:modelValue="account.snoozeMailboxId"
			:account="account"
			:disabled="saving.snoozeMailboxId"
			@update:modelValue="(folderId) => updateFolder('snoozeMailboxId', folderId)" />

		<p>
			{{ t('mail', 'Junk messages are saved in:') }}
		</p>
		<MailboxInlinePicker
			:modelValue="account.junkMailboxId"
			:account="account"
			:disabled="saving.junkMailboxId"
			@update:modelValue="(folderId) => updateFolder('junkMailboxId', folderId)" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import MailboxInlinePicker from './MailboxInlinePicker.vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'AccountDefaultsSettings',
	components: {
		MailboxInlinePicker,
		NcCheckboxRadioSwitch,
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

			savingSaveSentCopy: false,
		}
	},

	computed: {
		...mapStores(useMainStore),

		saveSentCopy: {
			get() {
				return this.account.saveSentCopy
			},

			async set(saveSentCopy) {
				this.savingSaveSentCopy = true
				try {
					await this.mainStore.patchAccount({
						account: this.account,
						data: {
							saveSentCopy,
						},
					})
				} catch (error) {
					logger.error('could not change saving a copy of sent messages', {
						error,
					})
				} finally {
					this.savingSaveSentCopy = false
				}
			},
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
