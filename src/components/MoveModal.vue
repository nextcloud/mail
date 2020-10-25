<template>
	<MailboxPicker :account="account"
		:selected.sync="destMailboxId"
		:loading="moving"
		:label-select="t('mail', 'Move')"
		:label-select-loading="t('mail', 'Moving')"
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
	},
	data() {
		return {
			moving: false,
			hasMovedEnvelopes: false,
			destMailboxId: undefined,
		}
	},
	methods: {
		onClose() {
			this.$emit('close')
			if (this.hasMovedEnvelopes) {
				this.$store.dispatch('syncEnvelopes', { mailboxId: this.destMailboxId })
			}
		},
		async onMove() {
			this.moving = true

			try {
				const envelopeIds = this.envelopes
					.filter((envelope) => envelope.mailboxId !== this.destMailboxId)
					.map((envelope) => envelope.databaseId)

				if (envelopeIds.length === 0) {
					return
				}

				await Promise.all(envelopeIds.map(async(id) => {
					await this.$store.dispatch('moveMessage', {
						id,
						destMailboxId: this.destMailboxId,
					})

					this.hasMovedEnvelopes = true
				}))

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
