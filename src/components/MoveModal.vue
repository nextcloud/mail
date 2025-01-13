<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxPicker :account="account"
		:selected.sync="destMailboxId"
		:loading="moving"
		:label-select="moveThread ? t('mail', 'Move thread') : t('mail', 'Move message')"
		:label-select-loading="moveThread ? t('mail', 'Moving thread') : t('mail', 'Moving message')"
		@select="onMove"
		@close="onClose" />
</template>

<script>
import logger from '../logger.js'
import MailboxPicker from './MailboxPicker.vue'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'MoveModal',
	components: {
		MailboxPicker,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		envelopes: {
			type: Array,
			required: true,
		},
		moveThread: {
			type: Boolean,
			default: false,
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

			try {
				const envelopes = this.envelopes
					.filter(envelope => envelope.mailboxId !== this.destMailboxId)

				if (envelopes.length === 0) {
					return
				}

				for (const envelope of envelopes) {
					if (this.moveThread) {
						await this.mainStore.moveThread({ envelope, destMailboxId: this.destMailboxId })
					} else {
						await this.mainStore.moveMessage({ id: envelope.databaseId, destMailboxId: this.destMailboxId })
					}
				}

				await this.mainStore.syncEnvelopes({ mailboxId: this.destMailboxId })
				this.$emit('move')
			} catch (error) {
				logger.error('could not move messages', {
					error,
				})
			} finally {
				this.moving = false
				this.$emit('close')
			}
		},
	},
}
</script>
