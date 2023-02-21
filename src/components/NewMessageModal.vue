<template>
	<Modal
		size="normal"
		:title="modalTitle"
		@close="$emit('close', { restoreOriginalSendAt: true })">
		<EmptyContent v-if="error"
			:title="t('mail', 'Error sending your message')"
			class="centered-content"
			role="alert">
			<p>{{ error }}</p>
			<template #action>
				<ButtonVue type="tertiary" @click="state = STATES.EDITING">
					{{ t('mail', 'Go back') }}
				</ButtonVue>
				<ButtonVue type="tertiary" @click="onSend">
					{{ t('mail', 'Retry') }}
				</ButtonVue>
			</template>
		</EmptyContent>
		<Loading v-else-if="uploadingAttachments" :hint="t('mail', 'Uploading attachments …')" role="alert" />
		<Loading v-else-if="sending"
			:hint="t('mail', 'Sending …')"
			role="alert" />
		<EmptyContent v-else-if="warning" :title="t('mail', 'Warning sending your message')" role="alert">
			<p>{{ warning }}</p>
			<ButtonVue type="tertiary" @click="state = STATES.EDITING">
				{{ t('mail', 'Go back') }}
			</ButtonVue>
			<ButtonVue type="tertiary" @click="onForceSend">
				{{ t('mail', 'Send anyway') }}
			</ButtonVue>
		</EmptyContent>
		<Composer v-else
			:from-account="composerData.accountId"
			:from-alias="composerData.aliasId"
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
			:forwarded-messages="forwardedMessages"
			:can-save-draft="canSaveDraft"
			:saving-draft="savingDraft"
			:draft-saved="draftSaved"
			:smime-sign="composerData.smimeSign"
			:smime-encrypt="composerData.smimeEncrypt"
			@draft="onDraft"
			@discard-draft="discardDraft"
			@upload-attachment="onAttachmentUploading"
			@send="onSend" />
	</Modal>
</template>
<script>
import {
	NcButton as ButtonVue,
	NcEmptyContent as EmptyContent,
	NcModal as Modal,
} from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger'
import { toPlain, toHtml, plain } from '../util/text'
import { saveDraft } from '../service/MessageService'
import Composer from './Composer'
import { UNDO_DELAY } from '../store/constants'
import { matchError } from '../errors/match'
import NoSentMailboxConfiguredError from '../errors/NoSentMailboxConfiguredError'
import ManyRecipientsError from '../errors/ManyRecipientsError'
import Loading from './Loading'

export default {
	name: 'NewMessageModal',
	components: {
		ButtonVue,
		Composer,
		EmptyContent,
		Loading,
		Modal,
	},
	data() {
		return {
			original: undefined,
			draftsPromise: Promise.resolve(this.composerData?.draftId),
			attachmentsPromise: Promise.resolve(),
			canSaveDraft: true,
			savingDraft: false,
			draftSaved: false,
			uploadingAttachments: false,
			sending: false,
			error: undefined,
			warning: undefined,
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
		onDraft(data) {
			if (!this.composerMessage) {
				logger.info('Ignoring draft because there is no message anymore', { data })
				return this.draftsPromise
			}

			this.draftsPromise = this.draftsPromise.then(async (id) => {
				this.savingDraft = true
				this.draftSaved = false
				data.draftId = id
				try {
					if (this.composerMessage.type === 'outbox') {
						const dataForServer = this.getDataForServer(data)
						await this.$store.dispatch('outbox/updateMessage', {
							message: dataForServer,
							id: this.composerData.id,
						})
						this.canSaveDraft = true
						this.draftSaved = true
					} else {
						const dataForServer = this.getDataForServer(data, true)
						const { id } = await saveDraft(data.account, dataForServer)
						this.canSaveDraft = true
						this.draftSaved = true

						// Remove old draft envelope
						this.$store.commit('removeEnvelope', { id: data.draftId })
						this.$store.commit('removeMessage', { id: data.draftId })

						// Fetch new draft envelope
						await this.$store.dispatch('fetchEnvelope', {
							accountId: data.account,
							id,
						})

						return id
					}
				} catch (error) {
					logger.error('Could not save draft', { error })
					this.canSaveDraft = false
				} finally {
					this.savingDraft = false
				}
			})

			return this.draftsPromise
		},
		getDataForServer(data, serializeRecipients = false) {
			return {
				...data,
				accountId: data.account,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
				editorBody: data.body.value,
				to: serializeRecipients ? data.to.map(this.recipientToRfc822).join(', ') : data.to,
				cc: serializeRecipients ? data.cc.map(this.recipientToRfc822).join(', ') : data.cc,
				bcc: serializeRecipients ? data.bcc.map(this.recipientToRfc822).join(', ') : data.bcc,
				attachments: data.attachments,
				aliasId: data.aliasId,
				inReplyToMessageId: data.inReplyToMessageId,
				sendAt: data.sendAt,
				draftId: data.draftId,
			}
		},
		onAttachmentUploading(done, data) {
			this.attachmentsPromise = this.attachmentsPromise
				.then(done)
				.then(() => this.onDraft(data))
				.then(() => logger.debug('attachments uploaded'))
				.catch((error) => logger.error('could not upload attachments', { error }))
		},
		async onSend(data) {
			logger.debug('sending message', { data })

			await this.attachmentsPromise
			this.uploadingAttachments = false
			this.sending = true
			try {
				const now = new Date().getTime()
				for (const attachment of data.attachments) {
					if (!attachment.type) {
						// todo move to backend: https://github.com/nextcloud/mail/issues/6227
						attachment.type = 'local'
					}
				}
				const dataForServer = this.getDataForServer({
					...data,
					draftId: await this.draftsPromise,
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
			} catch (error) {
				logger.error('could not send message', { error })
				this.error = await matchError(error, {
					[NoSentMailboxConfiguredError.getName()]() {
						return t('mail', 'No sent mailbox configured. Please pick one in the account settings.')
					},
					[ManyRecipientsError.getName()]() {
						return t('mail', 'You are trying to send to many recipients in To and/or Cc. Consider using Bcc to hide recipient addresses.')
					},
					default(error) {
						if (error && error.toString) {
							return error.toString()
						}
					},
				})
			} finally {
				this.sending = false
			}

			// Sync sent mailbox when it's currently open
			const account = this.$store.getters.getAccount(data.accountId)
			if (account && parseInt(this.$route.params.mailboxId, 10) === account.sentMailboxId) {
				setTimeout(() => {
					this.$store.dispatch('syncEnvelopes', {
						mailboxId: account.sentMailboxId,
						query: '',
						init: false,
					})
				}, 500)
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
		async discardDraft() {
			let id = await this.draftsPromise
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
				logger.error('Could not discard draft', { error })
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
