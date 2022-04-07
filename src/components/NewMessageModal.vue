<template>
	<Modal
		size="normal"
		:title="modalTitle"
		@close="$emit('close')">
		<Composer
			:from-account="composerData.accountId"
			:to="composerData.to"
			:cc="composerData.cc"
			:bcc="composerData.bcc"
			:subject="composerData.subject"
			:body="composerData.body"
			:draft="saveDraft"
			:send="sendMessage"
			:forwarded-messages="forwardedMessages" />
	</Modal>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import logger from '../logger'
import { toPlain } from '../util/text'
import { saveDraft } from '../service/MessageService'
import Composer from './Composer'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'NewMessageModal',
	components: {
		Modal,
		Composer,
	},
	data() {
		return {
			original: undefined,
			originalBody: undefined,
		}
	},
	computed: {
		modalTitle() {
			if (this.composerMessage.type === 'outbox') {
				return t('mail', 'Outbox draft')
			}
			return t('mail', 'New message')
		},
		composerMessage() {
			return this.$store.getters.composerMessage
		},
		composerData() {
			return this.$store.getters.composerMessage?.data
		},
		forwardedMessages() {
			return this.composerMessage?.options?.forwardedMessages ?? []
		},
	},
	methods: {
		async saveDraft(data) {
			if (this.composerMessage.type === 'outbox') {
				const dataForServer = {
					...data,
					body: data.isHtml ? data.body.value : toPlain(data.body).value,
				}
				await this.$store.dispatch('outbox/updateMessage', { message: dataForServer, id: this.composerData.id })
			} else {
				const dataForServer = {
					...data,
					to: data.to.map(this.recipientToRfc822).join(', '),
					cc: data.cc.map(this.recipientToRfc822).join(', '),
					bcc: data.bcc.map(this.recipientToRfc822).join(', '),
					body: data.isHtml ? data.body.value : toPlain(data.body).value,
				}
				const { id } = await saveDraft(data.account, dataForServer)

				// Remove old draft envelope
				this.$store.commit('removeEnvelope', { id: data.draftId })
				this.$store.commit('removeMessage', { id: data.draftId })

				// Fetch new draft envelope
				await this.$store.dispatch('fetchEnvelope', id)

				return id
			}
		},
		async sendMessage(data) {
			logger.debug('sending message', { data })
			if (this.composerMessage.type === 'outbox') {
				const now = new Date().getTime()
				const dataForServer = {
					accountId: data.account,
					subject: data.subject,
					body: data.isHtml ? data.body.value : toPlain(data.body).value,
					isHtml: data.isHtml,
					to: data.to,
					cc: data.cc,
					bcc: data.bcc,
					attachments: data.attachments,
					aliasId: data.aliasId,
					inReplyToMessageId: null,
					sendAt: Math.floor(now / 1000), // JS timestamp is in milliseconds
				}
				// TODO: update the message instead of enqueing another time
				const message = await this.$store.dispatch('outbox/enqueueMessage', {
					message: dataForServer,
				})

				await this.$store.dispatch('outbox/sendMessage', { id: message.id })
			} else {
				const now = new Date().getTime()
				const dataForServer = {
					accountId: data.account,
					subject: data.subject,
					body: data.isHtml ? data.body.value : toPlain(data.body).value,
					isHtml: data.isHtml,
					to: data.to,
					cc: data.cc,
					bcc: data.bcc,
					attachments: data.attachments,
					aliasId: data.aliasId,
					inReplyToMessageId: null,
					sendAt: Math.floor(now / 1000), // JS timestamp is in milliseconds
				}
				const message = await this.$store.dispatch('outbox/enqueueMessage', {
					message: dataForServer,
				})

				await this.$store.dispatch('outbox/sendMessage', { id: message.id })

				if (data.draftId) {
					// Remove old draft envelope
					this.$store.commit('removeEnvelope', { id: data.draftId })
					this.$store.commit('removeMessage', { id: data.draftId })
				}
			}
		},
		recipientToRfc822(recipient) {
			if (recipient.email === recipient.label) {
				// From mailto or sender without proper label
				return recipient.email
			} else if (recipient.label === '') {
				// Invalid label
				return recipient.email
			} else if (recipient.email.search(/^[a-zA-Z]+:/) === 0) {
				// Group integration
				return recipient.email
			} else {
				// Proper layout with label
				return `"${recipient.label}" <${recipient.email}>`
			}
		},
	},
}

</script>

<style lang="scss" scoped>
@media only screen and (max-width: 600px) {
	::v-deep .modal-container {
		max-width: 80%;
	}
}
::v-deep .modal-container {
	width: 80%;
	min-height: 60%;
}
::v-deep .modal-wrapper .modal-container {
	overflow-y: auto !important;
	overflow-x: auto !important;
}
</style>
