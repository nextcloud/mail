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
import logger from '../logger'
import MailboxPicker from './MailboxPicker'

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

				// Move messages per batch of 50 messages so as to not overload server or create timeouts
				while (envelopeIds.length > 0) {
					const batch = envelopeIds.splice(-50)
					await this.$store.dispatch('moveThreads', {
						ids: batch,
						destMailboxId: this.destMailboxId,
					})
				}

				await this.$store.dispatch('syncEnvelopes', { mailboxId: this.destMailboxId })
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
