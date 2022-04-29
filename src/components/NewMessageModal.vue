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
			:attachments-data="composerData.attachments"
			:body="composerData.body"
			:reply-to="composerData.replyTo"
			:draft-id="composerData.draftId"
			:send-at="composerData.sendAt * 1000"
			:draft="saveDraft"
			:send="sendMessage"
			:forwarded-messages="forwardedMessages"
			@discardDraft="discardDraft"
			@close="$emit('close')" />
	</Modal>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import logger from '../logger'
import { toPlain } from '../util/text'
import { saveDraft } from '../service/MessageService'
import Composer from './Composer'
import { showError, showSuccess, showUndo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

const UNDO_DELAY = 7 * 1000

export default {
	name: 'NewMessageModal',
	components: {
		Modal,
		Composer,
	},
	data() {
		return {
			original: undefined,
			draftsPromise: Promise.resolve(this.draftId),
		}
	},
	computed: {
		modalTitle() {
			if (this.composerMessage.type === 'outbox') {
				return t('mail', 'Edit message')
			}
			if (this.composerData.draftId !== undefined) {
				return t('mail', 'Draft')
			}
			if (this.composerData.replyTo) {
				return t('mail', 'Reply')
			}
			if (this.composerData.forwardFrom) {
				return t('mail', 'Forward')
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
			if (!this.composerMessage) {
				logger.info('Ignoring draft because there is no message anymore', { data })
				return
			}

			if (this.composerMessage.type === 'outbox') {
				logger.info('skipping autosave', { data })
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
			const now = new Date().getTime()
			for (const attachment of data.attachments) {
				if (!attachment.type) {
					// todo move to backend: https://github.com/nextcloud/mail/issues/6227
					attachment.type = 'local'
				}
			}
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
				sendAt: data.sendAt ? data.sendAt : Math.floor((now + UNDO_DELAY) / 1000),
			}
			if (dataForServer.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
				dataForServer.sendAt = Math.floor((now + UNDO_DELAY) / 1000)
			}

			let message
			if (!this.composerData.id) {
				message = await this.$store.dispatch('outbox/enqueueMessage', {
					message: dataForServer,
				})
			} else {
				message = await this.$store.dispatch('outbox/updateMessage', {
					message: dataForServer,
					id: this.composerData.id,
				})
			}

			if (!data.sendAt || data.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
				showUndo(
					t('mail', 'Message sent'),
					async() => {
						logger.info('Attempting to stop sending message ' + message.id)
						const stopped = await this.$store.dispatch('outbox/stopMessage', { message })
						logger.info('Message ' + message.id + ' stopped', { message: stopped })
						await this.$store.dispatch('showMessageComposer', {
							type: 'outbox',
							data: {
								...message, // For id and other properties
								...data, // For the correct body values
							},
						})
					}, {
						timeout: UNDO_DELAY,
						close: true,
					}
				)

				setTimeout(() => {
					try {
						this.$store.dispatch('outbox/sendMessage', { id: message.id })
					} catch (error) {
						showError(t('mail', 'Could not send message'))
						logger.error('Could not delay-send message ' + message.id, { message })
					}
				}, UNDO_DELAY)
			}
			if (data.draftId) {
				// Remove old draft envelope
				this.$store.commit('removeEnvelope', { id: data.draftId })
				this.$store.commit('removeMessage', { id: data.draftId })
			}
			this.$emit('close')
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
		async discardDraft(id) {
			console.debug('discarding working?', id)
			const isOutbox = this.composerMessage.type === 'outbox'
			if (isOutbox) {
				id = this.composerMessage.data.id
			}
			this.$emit('close')
			try {
				if (isOutbox) {
					await this.$store.dispatch('outbox/deleteMessage', { id })
				} else {
					await this.$store.dispatch('deleteMessage', { id })
				}
				showSuccess(t('mail', 'Message discarded'))
			} catch (error) {
				console.error(error)
				showError(t('mail', 'Could not discard message'))
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
