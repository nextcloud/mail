<template>
	<div v-if="hasMdnRequest && !mdnSent" class="mail-message-has-mdn-request">
		{{
			t('mail', 'The sender of this message has asked to be notified when you read this message.')
		}}
		<button :disabled="loading" @click="sendMdn">
			{{ t('mail', 'Notify the sender') }}
		</button>
	</div>
	<div v-else-if="mdnSent" class="mail-message-has-mdn-request">
		{{
			t('mail', 'You sent a read confirmation to the sender of this message.')
		}}
	</div>
</template>

<script>
import logger from '../logger.js'
import { sendMdn } from '../service/MessageService.js'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'MdnRequest',
	props: {
		message: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			hasMdnRequest: this.message.dispositionNotificationTo && this.message.dispositionNotificationTo.length > 0,
			loading: false,
		}
	},
	computed: {
		mdnSent() {
			return this.message.flags.mdnsent
		},
	},
	methods: {
		async sendMdn() {
			this.loading = true
			logger.debug('send return receipt')

			try {
				await sendMdn(this.message.databaseId)
				this.$store.commit('flagEnvelope', { envelope: this.message, flag: 'mdnsent', value: true })
			} catch (error) {
				logger.error('could not send mdn', error)
				showError(t('mail', 'Could not send mdn'))
			}

			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
.mail-message-has-mdn-request {
	white-space: normal;
}
</style>
