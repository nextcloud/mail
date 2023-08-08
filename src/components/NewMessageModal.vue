
<template>
	<Modal
		v-if="showMessageComposer"
		size="normal"
		:title="modalTitle"
		:additional-trap-elements="toolbarElements"
		@close="$event.type === 'click' ? onClose() : onMinimize()">
		<EmptyContent v-if="error"
			:title="t('mail', 'Error sending your message')"
			class="centered-content"
			role="alert">
			<p>{{ error }}</p>
			<template #action>
				<ButtonVue
					type="tertiary"
					:aria-label="t('mail', 'Go back')"
					@click="error = undefined">
					{{ t('mail', 'Go back') }}
				</ButtonVue>
				<ButtonVue
					type="tertiary"
					:aria-label="t('mail', 'Retry')"
					@click="onSend">
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
			<ButtonVue
				type="tertiary"
				:aria-label="t('mail', 'Go back')"
				@click="warning = undefined">
				{{ t('mail', 'Go back') }}
			</ButtonVue>
			<ButtonVue
				type="tertiary"
				:aria-label="t('mail', 'Send anyway')"
				@click="onForceSend">
				{{ t('mail', 'Send anyway') }}
			</ButtonVue>
		</EmptyContent>
		<template v-else>
			<NcActions class="minimize-button">
				<NcActionButton :aria-label="t('mail', 'Minimize composer')" @click="onMinimize">
					<template #icon>
						<MinimizeIcon :size="20" />
					</template>
				</NcActionButton>
			</NcActions>
			<Composer ref="composer"
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
				:send-at="composerData.sendAt * 1000"
				:forwarded-messages="forwardedMessages"
				:can-save-draft="canSaveDraft"
				:saving-draft="savingDraft"
				:draft-saved="draftSaved"
				:smime-sign="composerData.smimeSign"
				:smime-encrypt="composerData.smimeEncrypt"
				:is-first-open="modalFirstOpen"
				:request-mdn="composerData.requestMdn"
				@update:from-account="patchComposerData({ accountId: $event })"
				@update:from-alias="patchComposerData({ aliasId: $event })"
				@update:to="patchComposerData({ to: $event })"
				@update:cc="patchComposerData({ cc: $event })"
				@update:bcc="patchComposerData({ bcc: $event })"
				@update:subject="patchComposerData({ subject: $event })"
				@update:attachments-data="patchComposerData({ attachments: $event })"
				@update:editor-body="patchComposerData({ editorBody: $event })"
				@update:send-at="patchComposerData({ sendAt: $event / 1000 })"
				@update:smime-sign="patchComposerData({ smimeSign: $event })"
				@update:smime-encrypt="patchComposerData({ smimeSign: $event })"
				@update:request-mdn="patchComposerData({ requestMdn: $event })"
				@draft="onDraft"
				@discard-draft="discardDraft"
				@upload-attachment="onAttachmentUploading"
				@send="onSend"
				@show-toolbar="handleShow" />
		</template>
	</Modal>
</template>
<script>
import {
	NcButton as ButtonVue,
	NcEmptyContent as EmptyContent,
	NcModal as Modal,
	NcActions,
	NcActionButton,
} from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger'
import { toPlain, toHtml, plain } from '../util/text'
import Composer from './Composer'
import { UNDO_DELAY } from '../store/constants'
import { matchError } from '../errors/match'
import NoSentMailboxConfiguredError from '../errors/NoSentMailboxConfiguredError'
import ManyRecipientsError from '../errors/ManyRecipientsError'
import Loading from './Loading'
import { mapGetters } from 'vuex'
import MinimizeIcon from 'vue-material-design-icons/Minus.vue'
import { deleteDraft, saveDraft, updateDraft } from '../service/DraftService'

export default {
	name: 'NewMessageModal',
	components: {
		ButtonVue,
		Composer,
		EmptyContent,
		Loading,
		Modal,
		NcActions,
		NcActionButton,
		MinimizeIcon,
	},
	data() {
		return {
			toolbarElements: undefined,
			original: undefined,
			draftsPromise: Promise.resolve(),
			attachmentsPromise: Promise.resolve(),
			canSaveDraft: true,
			savingDraft: false,
			draftSaved: false,
			uploadingAttachments: false,
			sending: false,
			error: undefined,
			warning: undefined,
			modalFirstOpen: true,
			cookedComposerData: undefined,
			changed: false,
		}
	},
	computed: {
		...mapGetters(['showMessageComposer']),
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
			return this.$store.getters.composerMessage?.data ?? {}
		},
		forwardedMessages() {
			return this.composerMessage?.options?.forwardedMessages ?? []
		},
	},
	created() {
		const id = this.composerData?.id
		if (id) {
			this.draftsPromise = Promise.resolve(id)
		}
	},
	async mounted() {
		await this.$nextTick()
		this.updateCookedComposerData()
	},
	methods: {
		handleShow(element) {
			this.toolbarElements = [element]
		},
		toHtml,
		plain,
		/**
		 * @param data Message data
		 * @param {object=} opts Options
		 * @param {boolean=} opts.showToast Show a toast after saving
		 * @return {Promise<number>} Draft id promise
		 */
		// TODO: when there's no draft is saved, Cloing wont move ie case 2 doesn't work
		onDraft(data, { showToast = false } = {}) {
			if (!this.composerMessage) {
				logger.info('Ignoring draft because there is no message anymore', { data })
				return this.draftsPromise
			}
			this.changed = true

			this.draftsPromise = this.draftsPromise.then(async (id) => {
				this.savingDraft = true
				this.draftSaved = false
				try {
					let idToReturn
					const dataForServer = this.getDataForServer(data, true)
					if (!id) {
						const { id } = await saveDraft(dataForServer)
						dataForServer.id = id
						await this.$store.dispatch('patchComposerData', { id, draftId: dataForServer.draftId })
						this.canSaveDraft = true
						this.draftSaved = true

						idToReturn = id
					} else {
						dataForServer.id = id
						await updateDraft(dataForServer)
						this.canSaveDraft = true
						this.draftSaved = true
						idToReturn = id
					}

					this.$store.commit('setComposerMessageSaved', true)

					if (showToast) {
						if (this.composerMessage.type === 'outbox') {
							showSuccess(t('mail', 'Message saved'))
						} else {
							showSuccess(t('mail', 'Draft saved'))
						}
					}

					if (idToReturn !== undefined) {
						return idToReturn
					}
				} catch (error) {
					logger.error('Could not save draft', { error })
					this.canSaveDraft = false
					this.$store.commit('setComposerIndicatorDisabled', false)

					if (showToast) {
						if (this.composerMessage.type === 'outbox') {
							showError(t('mail', 'Failed to save message'))
						} else {
							showError(t('mail', 'Failed to save draft'))
						}
					}
				} finally {
					this.savingDraft = false
				}
			})

			return this.draftsPromise
		},
		getDataForServer(data) {
			return {
				...data,
				id: data.id,
				accountId: data.accountId,
				body: data.isHtml ? data.body.value : toPlain(data.body).value,
				editorBody: data.body.value,
				to: data.to,
				cc: data.cc,
				bcc: data.bcc,
				attachments: data.attachments,
				aliasId: data.aliasId,
				inReplyToMessageId: data.inReplyToMessageId,
				sendAt: data.sendAt,
				draftId: this.composerData?.draftId,
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
					id: await this.draftsPromise,
					sendAt: data.sendAt ? data.sendAt : Math.floor((now + UNDO_DELAY) / 1000),
				})
				if (dataForServer.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
					dataForServer.sendAt = Math.floor((now + UNDO_DELAY) / 1000)
				}

				if (!this.composerData.id) {
					// This is a new message
					const { id } = await saveDraft(dataForServer)
					dataForServer.id = id
					await this.$store.dispatch('outbox/enqueueMessage', {
						message: dataForServer,
					})
				} else if (this.composerData.type === 0) {
					// This is an outbox message
					dataForServer.id = this.composerData.id
					await this.$store.dispatch('outbox/updateMessage', {
						message: dataForServer,
						id: this.composerData.id,
					})
				} else {
					// This is a draft
					dataForServer.id = this.composerData.id
					await this.$store.dispatch('outbox/enqueueFromDraft', {
						draftMessage: dataForServer,
						id: this.composerData.id,
					})
				}

				if (!data.sendAt || data.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
					// Awaiting here would keep the modal open for a long time and thus block the user
					this.$store.dispatch('outbox/sendMessageWithUndo', { id: dataForServer.id })
				}
				if (dataForServer.id) {
					// Remove old draft envelope
					this.$store.commit('removeMessage', { id: dataForServer.id })
				}
				await this.$store.dispatch('stopComposerSession')
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
		async onForceSend() {
			await this.onSend(null, true)
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

			// It's safe to stop the session and ultimately destroy this component as only data
			// local this this function is accessed afterwards
			await this.$store.dispatch('stopComposerSession')

			try {
				if (isOutbox) {
					await this.$store.dispatch('outbox/deleteMessage', { id })
				} else {
					deleteDraft(id)
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
		updateCookedComposerData() {
			if (!this.$refs.composer) {
				// Composer is not rendered yet
				return
			}

			// Extract data to save drafts while the composer is not rendered.
			// This is hacky but there is no other way for now.
			this.cookedComposerData = this.$refs.composer.getMessageData()
		},
		async patchComposerData(data) {
			this.changed = true
			this.updateCookedComposerData()
			await this.$store.dispatch('patchComposerData', data)
		},
		async onMinimize() {
			this.modalFirstOpen = false

			await this.$store.dispatch('closeMessageComposer')
			if (!this.$store.getters.composerMessageIsSaved && this.changed) {
				await this.onDraft(this.cookedComposerData, { showToast: true })
			}

		},
		async onClose() {
			this.$store.commit('setComposerIndicatorDisabled', true)
			await this.onMinimize()

			// End the session only if all unsaved changes have been saved
			if (this.canSaveDraft && ((this.changed && this.draftSaved) || !this.changed)) {
				logger.debug('Closing composer session due to close button click')
				await this.$store.dispatch('stopComposerSession', {
					restoreOriginalSendAt: true,
					moveToImap: this.changed,
					id: this.composerData.id,
				})

			}
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

.minimize-button {
	position: absolute;
	right: 49px;
	top: 4px;
}
</style>
