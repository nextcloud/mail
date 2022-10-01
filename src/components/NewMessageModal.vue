<template>
	<Modal
		size="normal"
		:title="modalTitle"
		@close="$emit('close', { restoreOriginalSendAt: true })">
		<Composer
			:from-account="composerData.accountId"
			:to="composerData.to"
			:cc="composerData.cc"
			:bcc="composerData.bcc"
			:subject="composerData.subject"
			:attachments-data="composerData.attachments"
			:body="composerData.body"
			:editor-body="convertEditorBody(composerData)"
			:in-reply-to-message-id="composerData.inReplyToMessageId"
			:reply-to="composerData.replyTo"
			:forward-from="composerData.forwardFrom"
			:draft-id="composerData.draftId"
			:send-at="composerData.sendAt * 1000"
			:draft="saveDraft"
			:send="sendMessage"
			:forwarded-messages="forwardedMessages"
			@discard-draft="discardDraft" />
	</Modal>
</template>
<script>
import { NcModal as Modal } from '@nextcloud/vue'
import logger from '../logger'
import { toPlain, toHtml, plain } from '../util/text'
import { saveDraft } from '../service/MessageService'
import Composer from './Composer'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { UNDO_DELAY } from '../store/constants'

export default {
	name: 'NewMessageModal',
	components: {
		Modal,
		Composer,
	},
	data() {
		return {
			original: undefined,
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
		toHtml,
		plain,
		async saveDraft(data) {
			if (!this.composerMessage) {
				logger.info('Ignoring draft because there is no message anymore', { data })
				return
			}

			if (this.composerMessage.type === 'outbox') {
				const dataForServer = this.getDataForServer(data)
				await this.$store.dispatch('outbox/updateMessage', {
					message: dataForServer,
					id: this.composerData.id,
				})
			} else {
				const dataForServer = this.getDataForServer(data, true)
				const { id } = await saveDraft(data.account, dataForServer)

				// Remove old draft envelope
				this.$store.commit('removeEnvelope', { id: data.draftId })
				this.$store.commit('removeMessage', { id: data.draftId })

				// Fetch new draft envelope
				await this.$store.dispatch('fetchEnvelope', id)

				return id
			}
		},
		getDataForServer(data, serializeRecipients = false) {
			return {
				accountId: data.account,
				subject: data.subject,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
				editorBody: data.body.value,
				isHtml: data.isHtml,
				to: serializeRecipients ? data.to.map(this.recipientToRfc822).join(', ') : data.to,
				cc: serializeRecipients ? data.cc.map(this.recipientToRfc822).join(', ') : data.cc,
				bcc: serializeRecipients ? data.bcc.map(this.recipientToRfc822).join(', ') : data.bcc,
				attachments: data.attachments,
				aliasId: data.aliasId,
				inReplyToMessageId: data.inReplyToMessageId,
				sendAt: data.sendAt,
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
			const dataForServer = this.getDataForServer({
				...data,
				sendAt: data.sendAt ? data.sendAt : Math.floor((now + UNDO_DELAY) / 1000),
			})
			if (dataForServer.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
				dataForServer.sendAt = Math.floor((now + UNDO_DELAY) / 1000)
			}

			if (!this.composerData.id) {
				await this.$store.dispatch('outbox/enqueueMessage', {
					message: dataForServer,
				})
			} else {
				await this.$store.dispatch('outbox/updateMessage', {
					message: dataForServer,
					id: this.composerData.id,
				})
			}

			if (!data.sendAt || data.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
				// Awaiting here would keep the modal open for a long time and thus block the user
				this.$store.dispatch('outbox/sendMessageWithUndo', { id: this.composerData.id })
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
		convertEditorBody(composerData) {
			if (composerData.editorBody) {
				return composerData.editorBody
			}
			if (!composerData.body) {
				return ''
			}
			return toHtml(composerData.body).value
		},
	},
}

</script>

<style lang="scss" scoped>
@media only screen and (max-width: 600px) {
	:deep(.modal-container) {
		max-width: 80%;
	}
}
:deep(.modal-wrapper .modal-container) {
	overflow-y: auto !important;
	overflow-x: auto !important;
	// from original Modal max-height
	height: 90%;
	// Max editor + modal height
	max-height: 700px !important;
}
</style>
