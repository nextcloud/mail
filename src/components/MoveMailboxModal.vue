<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxPicker :account="account"
		:selected.sync="destMailboxId"
		:loading="moving"
		:picked-mailbox="mailbox"
		:allow-root="true"
		:label-select="t('mail', 'Move')"
		:label-select-loading="t('mail', 'Moving')"
		@select="onMove"
		@close="onClose" />
</template>

<script>
import logger from '../logger.js'
import MailboxPicker from './MailboxPicker.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'MoveMailboxModal',
	components: {
		MailboxPicker,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		mailbox: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			moving: false,
			destMailboxId: undefined,
		}
	},
	computed: {
		...mapStores(useMainStore),
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		async onMove() {
			this.moving = true
			if (this.mailbox.id !== this.destMailboxId) {
				try {
					if (!this.destMailboxId) {
						const newName = this.mailbox.displayName
						await this.mainStore.renameMailbox({
							account: this.account,
							mailbox: this.mailbox,
							newName,
						})

					} else {
						const destMailbox = this.mainStore.getMailbox(this.destMailboxId)
						const newName = destMailbox.name + this.mailbox.delimiter + this.mailbox.displayName
						await this.mainStore.renameMailbox({
							account: this.account,
							mailbox: this.mailbox,
							newName,
						})
					}
				} catch (error) {
					logger.error('could not move folder', {
						error,
					})
				} finally {
					this.moving = false
					this.$emit('close')
				}
			}
		},
		genId(mailbox) {
			return 'mailbox-' + mailbox.databaseId
		 },
	},
}
</script>
