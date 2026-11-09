<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<MailboxPicker
		v-model:selected="destMailboxId"
		:account="account"
		:loading="moving"
		:labelSelect="labelSelect"
		:labelSelectLoading="labelSelectLoading"
		:select="onTransfer"
		@close="onClose" />
</template>

<script>
import { mapStores } from 'pinia'
import MailboxPicker from './MailboxPicker.vue'
import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

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

		mode: {
			type: String,
			default: 'move',
			validator: (value) => ['move', 'copy'].includes(value),
		},
	},

	emits: ['close', 'move', 'copy'],

	data() {
		return {
			moving: false,
			destMailboxId: undefined,
		}
	},

	computed: {
		...mapStores(useMainStore),

		isCopy() {
			return this.mode === 'copy'
		},

		labelSelect() {
			if (this.isCopy) {
				return this.moveThread ? t('mail', 'Copy thread') : t('mail', 'Copy message')
			}
			return this.moveThread ? t('mail', 'Move thread') : t('mail', 'Move message')
		},

		labelSelectLoading() {
			if (this.isCopy) {
				return this.moveThread ? t('mail', 'Copying thread') : t('mail', 'Copying message')
			}
			return this.moveThread ? t('mail', 'Moving thread') : t('mail', 'Moving message')
		},
	},

	methods: {
		onClose() {
			this.$emit('close')
		},

		async onMove() {
			return await this.onTransfer()
		},

		async onTransfer() {
			this.moving = true

			try {
				const envelopes = this.envelopes
					.filter((envelope) => envelope.mailboxId !== this.destMailboxId)

				if (envelopes.length === 0) {
					return
				}

				for (const envelope of envelopes) {
					if (this.isCopy) {
						if (this.moveThread) {
							await this.mainStore.copyThread({ envelope, destMailboxId: this.destMailboxId })
						} else {
							await this.mainStore.copyMessage({ id: envelope.databaseId, destMailboxId: this.destMailboxId })
						}
					} else {
						if (this.moveThread) {
							await this.mainStore.moveThread({ envelope, destMailboxId: this.destMailboxId })
						} else {
							await this.mainStore.moveMessage({ id: envelope.databaseId, destMailboxId: this.destMailboxId })
						}
					}
				}

				await this.mainStore.syncEnvelopes({ mailboxId: this.destMailboxId })
				this.$emit(this.isCopy ? 'copy' : 'move')
			} catch (error) {
				logger.error(`could not ${this.mode} messages`, {
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
