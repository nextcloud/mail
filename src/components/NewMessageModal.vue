<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<Modal v-if="showMessageComposer"
		:size="modalSize"
		:name="modalTitle"
		:additional-trap-elements="additionalTrapElements"
		@close="$event.type === 'click' ? onClose() : onMinimize()">
		<EmptyContent v-if="error"
			:name="t('mail', 'Error sending your message')"
			class="empty-content"
			role="alert">
			<p>{{ error }}</p>
			<template #action>
				<NcButton type="tertiary"
					:aria-label="t('mail', 'Go back')"
					@click="error = undefined">
					{{ t('mail', 'Go back') }}
				</NcButton>
				<NcButton type="tertiary"
					:aria-label="t('mail', 'Retry')"
					@click="onSend">
					{{ t('mail', 'Retry') }}
				</NcButton>
			</template>
		</EmptyContent>
		<Loading v-else-if="uploadingAttachments"
			:hint="t('mail', 'Uploading attachments …')"
			role="alert" />
		<Loading v-else-if="sending" :hint="t('mail', 'Sending …')" role="alert" />
		<EmptyContent v-else-if="warning"
			:name="t('mail', 'Warning sending your message')"
			class="empty-content"
			role="alert">
			<template #description>
				{{ warning }}
			</template>
			<template #action>
				<NcButton type="tertiary"
					:aria-label="t('mail', 'Go back')"
					@click="warning = undefined">
					{{ t('mail', 'Go back') }}
				</NcButton>
				<NcButton type="tertiary"
					:aria-label="t('mail', 'Send anyway')"
					@click="onForceSend">
					{{ t('mail', 'Send anyway') }}
				</NcButton>
			</template>
		</EmptyContent>
		<template v-else>
			<div :class="['modal-content', { 'with-recipient': composerData.to && composerData.to.length > 0 }]">
				<div class="left-pane">
					<NcButton class="maximize-button"
						type="tertiary-no-background"
						:aria-label="t('mail', 'Maximize composer')"
						@click="onMaximize">
						<template #icon>
							<MaximizeIcon v-if="!largerModal" :size="20" />
							<DefaultComposerIcon v-else :size="20" />
						</template>
					</NcButton>
					<NcButton class="minimize-button"
						type="tertiary-no-background"
						:aria-label="t('mail', 'Minimize composer')"
						@click="onMinimize">
						<template #icon>
							<MinimizeIcon :size="20" />
						</template>
					</NcButton>

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
						:smart-reply="smartReply"
						:can-save-draft="canSaveDraft"
						:saving-draft="savingDraft"
						:draft-saved="draftSaved"
						:smime-sign="composerData.smimeSign"
						:smime-encrypt="composerData.smimeEncrypt"
						:is-first-open="modalFirstOpen"
						:request-mdn="composerData.requestMdn"
						:accounts="accounts"
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
				</div>

				<div v-if="composerData.to && composerData.to.length > 0" class="right-pane">
					<RecipientInfo :recipient-info="composerData.to" />
				</div>
			</div>
		</template>
	</Modal>
</template>
<script>
import {
	NcButton,
	NcEmptyContent as EmptyContent,
	NcModal as Modal,
} from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger.js'
import { toPlain, toHtml, plain } from '../util/text.js'
import Composer from './Composer.vue'
import { UNDO_DELAY } from '../store/constants.js'
import { matchError } from '../errors/match.js'
import NoSentMailboxConfiguredError from '../errors/NoSentMailboxConfiguredError.js'
import ManyRecipientsError from '../errors/ManyRecipientsError.js'
import AttachmentMissingError from '../errors/AttachmentMissingError.js'
import Loading from './Loading.vue'
import { mapGetters } from 'vuex'
import MinimizeIcon from 'vue-material-design-icons/Minus.vue'
import MaximizeIcon from 'vue-material-design-icons/ArrowExpand.vue'
import DefaultComposerIcon from 'vue-material-design-icons/ArrowCollapse.vue'
import { deleteDraft, saveDraft, updateDraft } from '../service/DraftService.js'
import useOutboxStore from '../store/outboxStore.js'
import { mapStores } from 'pinia'
import RecipientInfo from './RecipientInfo.vue'

export default {
	name: 'NewMessageModal',
	components: {
		NcButton,
		Composer,
		EmptyContent,
		Loading,
		Modal,
		MinimizeIcon,
		MaximizeIcon,
		DefaultComposerIcon,
		RecipientInfo,
	},
	props: {
		accounts: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			additionalTrapElements: ['#reference-picker'],
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
			largerModal: false,
			recipient: {
				name: '',
				email: '',
			},
		}
	},
	computed: {
		...mapStores(useOutboxStore),
		...mapGetters(['showMessageComposer', 'getPreference']),
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
		smartReply() {
			return this.composerData?.smartReply ?? null
		},
		modalSize() {
			return this.composerData.to && this.composerData.to.length > 0 ? 'full' : (this.largerModal ? 'large' : 'normal')
		},
	},
	created() {
		const id = this.composerData?.id
		if (id) {
			this.draftsPromise = Promise.resolve(id)
		}
		window.addEventListener('beforeunload', this.onBeforeUnload)
	},
	async mounted() {
		await this.$nextTick()
		this.updateCookedComposerData()
		await this.openModalSize()
	},
	beforeDestroy() {
		window.removeEventListener('beforeunload', this.onBeforeUnload)
	},
	methods: {
		async openModalSize() {
			try {
				const sizePreference = this.$store.getters.getPreference('modalSize')
				this.largerModal = sizePreference === 'large'
			} catch (error) {
				console.error('Error getting modal size preference', error)
			}
		},
		async onMaximize() {
			this.largerModal = !this.largerModal
			try {
				await this.$store.dispatch('savePreference', {
					key: 'modalSize',
					value: this.largerModal ? 'large' : 'normal',
				})
			} catch (error) {
				console.error('Failed to save preference', error)
			}
		},
		handleShow(element) {
			this.additionalTrapElements.push(element)
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
		async onSend(data, force = false) {
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

				if (!force && data.attachments.length === 0) {
					const lines = toPlain(data.body).value.toLowerCase().split('\n')
					const wordAttachment = t('mail', 'attachment').toLowerCase()
					const wordAttached = t('mail', 'attached').toLowerCase()
					for (const line of lines) {
						if (line.startsWith('>') || line.startsWith('--')) {
							break
						}
						if (line.includes(wordAttachment) || line.includes(wordAttached)) {
							throw new AttachmentMissingError()
						}
					}
				}

				if (!this.composerData.id) {
					// This is a new message
					const { id } = await saveDraft(dataForServer)
					dataForServer.id = id
					await this.outboxStore.enqueueFromDraft({
						draftMessage: dataForServer,
						id,
					})
				} else if (this.composerData.type === 0) {
					// This is an outbox message
					dataForServer.id = this.composerData.id
					await this.outboxStore.updateMessage({
						message: dataForServer,
						id: this.composerData.id,
					})
				} else {
					// This is a draft
					await updateDraft(dataForServer)
					dataForServer.id = this.composerData.id
					await this.outboxStore.enqueueFromDraft({
						draftMessage: dataForServer,
						id: this.composerData.id,
					})
				}

				if (!data.sendAt || data.sendAt < Math.floor((now + UNDO_DELAY) / 1000)) {
					// Awaiting here would keep the modal open for a long time and thus block the user
					this.outboxStore.sendMessageWithUndo({ id: dataForServer.id }).catch((error) => {
						logger.debug('Could not send message', { error })
					})
				}
				if (dataForServer.id) {
					// Remove old draft envelope
					this.$store.commit('removeMessage', { id: dataForServer.id })
				}
				await this.$store.dispatch('stopComposerSession')
				this.$emit('close')
			} catch (error) {
				this.error = await matchError(error, {
					[NoSentMailboxConfiguredError.getName()]() {
						logger.error('could not send message', { error })
						return t('mail', 'No sent mailbox configured. Please pick one in the account settings.')
					},
					[ManyRecipientsError.getName()]() {
						logger.error('could not send message', { error })
						return t('mail', 'You are trying to send to many recipients in To and/or Cc. Consider using Bcc to hide recipient addresses.')
					},
					// eslint-disable-next-line n/handle-callback-err
					default(error) {
						logger.error('could not send message', { error })
					},
				})
				this.warning = await matchError(error, {
					[AttachmentMissingError.getName()]() {
						logger.info('showing the did you forgot an attachment warning', { error })
						return t('mail', 'You mentioned an attachment. Did you forget to add it?')
					},
					// eslint-disable-next-line n/handle-callback-err
					default(error) {
						logger.warn('could not send message', { error })
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
			await this.onSend(this.cookedComposerData, true)
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
					await this.outboxStore.deleteMessage({ id })
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
		onBeforeUnload(e) {
			if (this.canSaveDraft && this.changed) {
				e.preventDefault()
				e.returnValue = true
				this.$store.dispatch('showMessageComposer')
			} else {
				console.info('No unsaved changes. See you!')
			}
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
	float: right;
	position: absolute;
	top: 4px;
	right: 63px;
}

.maximize-button {
	float: right;
	position: absolute;
	top: 4px;
	right: 33px;

}
.empty-content{
	height: 100%;
	display: flex;
}
.modal-content {
	display: flex;
	height: 100%;
	flex-direction: row;
	width: 100%;
}

.left-pane {
	flex: 1;
	overflow-y: auto;
}

.right-pane {
	flex: 0 0 400px;
	overflow-y: auto;
	padding-left: 5px;
	border-left: 1px solid #ccc;
}

.modal-content.with-recipient .left-pane {
	flex: 1;
	width: calc(100% - 400px);
}
.modal-content .left-pane {
	width: 100%;
}
</style>
