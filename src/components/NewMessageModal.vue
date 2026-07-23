<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div
		v-if="showMessageComposer"
		ref="floatingPanel"
		class="floating-composer"
		:class="{
			'floating-composer--maximized': largerModal,
			'floating-composer--with-recipient': showRecipientPane && !error && !warning && recipientPaneOpen,
		}"
		@mousedown.stop>
		<div class="floating-composer__header">
			<h2 class="floating-composer__title">
				{{ modalTitle }}
			</h2>
			<div class="floating-composer__actions">
				<NcButton
					variant="tertiary-no-background"
					:aria-label="largerModal ? t('mail', 'Restore composer') : t('mail', 'Maximize composer')"
					:title="largerModal ? t('mail', 'Restore composer') : t('mail', 'Maximize composer')"
					@click="onMaximize">
					<template #icon>
						<MaximizeIcon v-if="!largerModal" :size="20" />
						<DefaultComposerIcon v-else :size="20" />
					</template>
				</NcButton>
				<NcButton
					variant="tertiary-no-background"
					:aria-label="t('mail', 'Minimize composer')"
					:title="t('mail', 'Minimize composer')"
					@click="onMinimize">
					<template #icon>
						<MinimizeIcon :size="20" />
					</template>
				</NcButton>
				<NcButton
					variant="tertiary-no-background"
					:aria-label="t('mail', 'Close composer')"
					:title="t('mail', 'Close composer')"
					@click="onClose">
					<template #icon>
						<CloseIcon :size="20" />
					</template>
				</NcButton>
			</div>
		</div>
		<div class="floating-composer__content">
			<div v-if="showRecipientPane && !error && !warning && recipientPaneOpen" class="recipient-pane">
				<NcButton
					class="recipient-pane__close"
					variant="tertiary-no-background"
					:aria-label="t('mail', 'Close contact info')"
					@click="recipientPaneOpen = false">
					<template #icon>
						<CloseIcon :size="20" />
					</template>
				</NcButton>
				<RecipientInfo />
			</div>
			<div class="floating-composer__body">
				<KeepAlive>
					<EmptyContent
						v-if="error"
						:name="t('mail', 'Error sending your message')"
						class="empty-content"
						role="alert">
						<template #description>
							{{ error }}
						</template>
						<template #action>
							<NcButton variant="tertiary" :aria-label="t('mail', 'Go back')" @click="error = undefined">
								{{ t('mail', 'Go back') }}
							</NcButton>
							<NcButton variant="tertiary" :aria-label="t('mail', 'Retry')" @click="onSend">
								{{ t('mail', 'Retry') }}
							</NcButton>
						</template>
					</EmptyContent>
					<EmptyContent
						v-else-if="warning"
						:name="t('mail', 'Warning sending your message')"
						class="empty-content"
						role="alert">
						<template #description>
							{{ warning }}
						</template>
						<template #action>
							<NcButton variant="tertiary" :aria-label="t('mail', 'Go back')" @click="warning = undefined">
								{{ t('mail', 'Go back') }}
							</NcButton>
							<NcButton variant="tertiary" :aria-label="t('mail', 'Send anyway')" @click="onForceSend">
								{{ t('mail', 'Send anyway') }}
							</NcButton>
						</template>
					</EmptyContent>
					<Composer
						v-else
						ref="composer"
						:from-account="composerData.accountId"
						:from-alias="composerData.aliasId"
						:to="composerData.to"
						:cc="composerData.cc"
						:bcc="composerData.bcc"
						:subject="composerData.subject"
						:attachments-data="composerData.attachments"
						:body="composerDataBodyAsTextInstance"
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
						:is-draft="composerData.draftId !== undefined"
						:request-mdn="composerData.requestMdn"
						:accounts="accounts"
						@update:from-account="patchComposerData({ accountId: $event })"
						@update:from-alias="patchComposerData({ aliasId: $event })"
						@update:to="patchComposerData({ to: $event })"
						@update:cc="patchComposerData({ cc: $event })"
						@update:bcc="patchComposerData({ bcc: $event })"
						@update:subject="patchComposerData({ subject: $event })"
						@update:attachments-data="patchComposerData({ attachments: $event })"
						@update:editor-body="patchEditorBody"
						@update:send-at="patchComposerData({ sendAt: $event / 1000 })"
						@update:smime-sign="patchComposerData({ smimeSign: $event })"
						@update:smime-encrypt="patchComposerData({ smimeSign: $event })"
						@update:request-mdn="patchComposerData({ requestMdn: $event })"
						@draft="onDraft"
						@discard-draft="discardDraft"
						@upload-attachment="onAttachmentUploading"
						@send="onSend" />
				</KeepAlive>
			</div>
		</div>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	NcEmptyContent as EmptyContent,
	NcButton,
} from '@nextcloud/vue'
import { mapActions, mapState, mapStores } from 'pinia'
import DefaultComposerIcon from 'vue-material-design-icons/ArrowCollapse.vue'
import MaximizeIcon from 'vue-material-design-icons/ArrowExpand.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import MinimizeIcon from 'vue-material-design-icons/Minus.vue'
import Composer from './Composer.vue'
import RecipientInfo from './RecipientInfo.vue'
import AttachmentMissingError from '../errors/AttachmentMissingError.js'
import ManyRecipientsError from '../errors/ManyRecipientsError.js'
import { matchError } from '../errors/match.js'
import NoSentMailboxConfiguredError from '../errors/NoSentMailboxConfiguredError.js'
import SubjectMissingError from '../errors/SubjectMissingError.js'
import logger from '../logger.js'
import { deleteDraft, saveDraft, updateDraft } from '../service/DraftService.js'
import { UNDO_DELAY } from '../store/constants.js'
import useMainStore from '../store/mainStore.js'
import useOutboxStore from '../store/outboxStore.js'
import { messageBodyToTextInstance } from '../util/message.js'
import { toPlain } from '../util/text.js'

export default {
	name: 'NewMessageModal',
	components: {
		NcButton,
		Composer,
		EmptyContent,
		MinimizeIcon,
		RecipientInfo,
		MaximizeIcon,
		DefaultComposerIcon,
		CloseIcon,
	},

	provide() {
		return {
			addToFocusTrap: (trapElement) => this.additionalTrapElements.push(trapElement),
		}
	},

	props: {
		accounts: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
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
			additionalTrapElements: [],
			recipient: {
				name: '',
				email: '',
			},

			recipientPaneOpen: true,
		}
	},

	watch: {
		'composerData.to': function(newTo, oldTo) {
			if (newTo?.length > 0 && !oldTo?.length) {
				this.recipientPaneOpen = true
			}
		},
	},

	computed: {
		...mapStores(useOutboxStore, useMainStore),
		...mapState(useMainStore, ['showMessageComposer']),
		...mapActions(useMainStore, ['getPreference']),
		composerDataBodyAsTextInstance() {
			return messageBodyToTextInstance(this.composerData)
		},

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
			if (this.composerData.subject) {
				return this.composerData.subject
			}
			return t('mail', 'New message')
		},

		composerMessage() {
			return this.mainStore.composerMessage
		},

		composerData() {
			return this.mainStore.composerMessage?.data ?? {}
		},

		forwardedMessages() {
			return this.composerMessage?.options?.forwardedMessages ?? []
		},

		smartReply() {
			return this.composerData?.smartReply ?? null
		},

		showRecipientPane() {
			return this.composerData.to
				&& this.composerData.to.length > 0
				&& !this.largerModal
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
		document.addEventListener('mousedown', this.onClickOutside)
	},

	beforeDestroy() {
		window.removeEventListener('beforeunload', this.onBeforeUnload)
		document.removeEventListener('mousedown', this.onClickOutside)
	},

	methods: {
		async openModalSize() {
			try {
				const sizePreference = this.mainStore.getPreference('modalSize')
				this.largerModal = sizePreference === 'large'
			} catch (error) {
				logger.error('Error getting modal size preference', { error })
			}
		},

		onClickOutside(event) {
			if (!this.showMessageComposer) {
				return
			}
			if (!this.$refs.floatingPanel) {
				return
			}
			if (this.$refs.floatingPanel.contains(event.target)) {
				return
			}
			// Don't minimize when the user is interacting with a modal, popover, or CKEditor toolbar
			if (event.target.closest('.modal-wrapper, .ck-body-wrapper, [data-popper-placement], .v-popper__popper')) {
				return
			}
			this.onMinimize()
		},

		async onMaximize() {
			this.largerModal = !this.largerModal
			try {
				await this.mainStore.savePreference({
					key: 'modalSize',
					value: this.largerModal ? 'large' : 'normal',
				})
			} catch (error) {
				logger.error('Failed to save preference', { error })
			}
		},

		async onMinimize() {
			this.modalFirstOpen = false

			await this.mainStore.hideMessageComposerMutation()
			if (!this.mainStore.composerMessageIsSaved && this.changed) {
				await this.onDraft(this.cookedComposerData, { showToast: true })
			}
		},

		/**
		 * @param {object} data Message data
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
						if (dataForServer.draftId) {
							this.mainStore.removeEnvelopeMutation({ id: dataForServer.draftId })
						}
						const { id } = await saveDraft(dataForServer)
						dataForServer.id = id
						await this.mainStore.patchComposerData({ id, draftId: dataForServer.draftId })
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

					this.mainStore.setComposerMessageSavedMutation(true)

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
					this.mainStore.setComposerIndicatorDisabledMutation(false)

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
			const dataForServer = {
				...data,
				id: data.id,
				accountId: data.accountId,
				to: data.to,
				cc: data.cc,
				bcc: data.bcc,
				attachments: data.attachments,
				aliasId: data.aliasId,
				inReplyToMessageId: data.inReplyToMessageId,
				sendAt: data.sendAt,
				draftId: this.composerData?.draftId,
			}

			if (data.isHtml) {
				delete dataForServer.bodyPlain
			} else {
				delete dataForServer.bodyHtml
			}

			return dataForServer
		},

		onAttachmentUploading(done, data) {
			this.attachmentsPromise = this.attachmentsPromise
				.then(done)
				.then(() => this.onDraft(data))
				.then(() => logger.debug('Attachments uploaded'))
				.catch((error) => logger.error('Could not upload attachments', { error }))
		},

		async onSend(data, force = false) {
			logger.debug('sending message', { data })

			if (this.sending) {
				return
			}
			await this.attachmentsPromise
			this.uploadingAttachments = false
			this.sending = true
			this.$emit('close')
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
				if (!force && !data.subject?.trim()) {
					throw new SubjectMissingError()
				}

				if (!force && data.attachments.length === 0) {
					const lines = toPlain(messageBodyToTextInstance(data)).value.toLowerCase().split('\n')
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
					this.mainStore.removeMessageMutation({ id: dataForServer.id })
				}
				await this.mainStore.stopComposerSession()
			} catch (error) {
				this.error = await matchError(error, {
					[NoSentMailboxConfiguredError.getName()]() {
						logger.error('could not send message', { error })
						return t('mail', 'No "sent" folder configured. Please pick one in the account settings.')
					},
					[ManyRecipientsError.getName()]() {
						logger.error('could not send message', { error })
						return t('mail', 'You are trying to send to many recipients in To and/or Cc. Consider using Bcc to hide recipient addresses.')
					},
					default(error) {
						logger.error('could not send message', { error })
					},
				})
				this.warning = await matchError(error, {
					[SubjectMissingError.getName()]() {
						logger.info('showing the missing subject warning', { error })
						return t('mail', 'Your message has no subject. Do you want to send it anyway?')
					},
					[AttachmentMissingError.getName()]() {
						logger.info('showing the did you forgot an attachment warning', { error })
						return t('mail', 'You mentioned an attachment. Did you forget to add it?')
					},
					default(error) {
						logger.warn('Could not send message', { error })
					},
				})
			} finally {
				this.sending = false
			}

			// Sync sent mailbox when it's currently open
			const account = this.mainStore.getAccount(data.accountId)
			if (account && parseInt(this.$route.params.mailboxId, 10) === account.sentMailboxId) {
				setTimeout(() => {
					this.mainStore.syncEnvelopes({
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
			await this.mainStore.stopComposerSession()

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
			if (composerData.isHtml) {
				return composerData.bodyHtml
			}

			return composerData.bodyPlain
		},

		patchEditorBody(editorBody) {
			if (this.composerData.isHtml) {
				this.patchComposerData({ bodyHtml: editorBody })
			} else {
				this.patchComposerData({ bodyPlain: editorBody })
			}
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
			await this.mainStore.patchComposerData({ ...data, isHtml: this.cookedComposerData.isHtml })
		},

		onBeforeUnload(e) {
			if (this.canSaveDraft && this.changed) {
				e.preventDefault()
				e.returnValue = true
				this.mainStore.showMessageComposerMutation()
			} else {
				logger.debug('no unsaved changes, closing')
			}
		},

		async onClose() {
			this.mainStore.setComposerIndicatorDisabledMutation(true)
			await this.onMinimize()

			// End the session only if all unsaved changes have been saved
			if (this.canSaveDraft && ((this.changed && this.draftSaved) || !this.changed)) {
				logger.debug('Closing composer session due to close button click')
				await this.mainStore.stopComposerSession({
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
$composer-width: 600px;
$recipient-pane-width: 300px;
$composer-height: 500px;
$header-height: calc(var(--default-clickable-area) + calc(var(--default-grid-baseline) * 2));
$bottom-offset: calc(var(--body-container-margin, 0px) + var(--default-grid-baseline) * 2 + 52px);
$panel-max-height: calc(100vh - (var(--body-container-margin, 0px) + var(--default-grid-baseline) * 3 + 52px));

.floating-composer {
	position: fixed;
	bottom: $bottom-offset;
	inset-inline-end: calc(var(--body-container-margin, 0px) + var(--default-grid-baseline));
	z-index: 9999999;

	width: $composer-width;
	max-width: calc(100vw - 2 * var(--default-grid-baseline));
	height: $composer-height;
	max-height: $panel-max-height;

	display: flex;
	flex-direction: column;

	background-color: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);

	&--maximized {
		top: calc(var(--header-height, 44px) + var(--default-grid-baseline));
		height: auto;
	}

	&--with-recipient {
		width: calc(#{$composer-width} + #{$recipient-pane-width});
	}

	@media (max-width: #{$composer-width}) {
		inset-inline-end: 0;
		inset-inline-start: 0;
		bottom: 0;
		top: 0;
		width: 100%;
		height: 100%;
		max-height: 100%;
		border-radius: 0;
	}
}

.recipient-pane {
	flex: 0 0 $recipient-pane-width;
	overflow-y: auto;
	position: relative;
	border-inline-end: 1px solid var(--color-border);
	padding: calc(var(--default-grid-baseline) * 2);
	padding-top: calc(var(--default-grid-baseline) * 6);

	@media (max-width: #{$composer-width}) {
		display: none;
	}
}

.recipient-pane__close {
	position: absolute;
	top: var(--default-grid-baseline);
	inset-inline-end: var(--default-grid-baseline);
}

.floating-composer__header {
	position: relative;
	z-index: 101;
	display: flex;
	align-items: center;
	height: $header-height;
	flex-shrink: 0;
	padding: 0 calc(var(--default-grid-baseline) * 2);
	border-bottom: 1px solid var(--color-border);
	border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
	background-color: var(--color-main-background);
	cursor: default;
	user-select: none;
}

.floating-composer__title {
	flex: 1;
	margin: 0;
	font-size: var(--default-font-size);
	font-weight: bold;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.floating-composer__actions {
	display: flex;
	flex-shrink: 0;
	gap: 0;
}

.floating-composer__content {
	display: flex;
	flex-direction: row;
	flex: 1;
	min-height: 0;
	overflow: hidden;
}

.floating-composer__body {
	flex: 1;
	overflow-y: auto;
	min-height: 0;
	min-width: 0;
	display: flex;
	flex-direction: column;
}

.empty-content {
	height: 100%;
	display: flex;
}
</style>
