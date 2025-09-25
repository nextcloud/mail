<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div ref="envelope"
		class="envelope"
		:class="{'envelope--expanded' : expanded }">
		<div v-if="showFollowUpHeader"
			class="envelope__follow-up-header">
			<span class="envelope__follow-up-header__date">
				{{ t('mail', "You've sent this message on {date}", { date: formattedSentAt }) }}
			</span>
			<div class="envelope__follow-up-header__actions">
				<NcButton @click="onDisableFollowUpReminder">
					{{ t('mail', 'Disable reminder') }}
				</NcButton>
			</div>
		</div>

		<div class="envelope__header">
			<div class="envelope__header__avatar">
				<Avatar v-if="envelope.from && envelope.from[0]"
					:email="envelope.from[0].email"
					:display-name="envelope.from[0].label"
					:disable-tooltip="true"
					:size="40"
					:fetch-avatar="envelope.fetchAvatarFromClient"
					:avatar="envelope.avatar"
					class="envelope__header__avatar-avatar" />
				<div v-if="isImportant"
					class="app-content-list-item-star icon-important"
					:data-starred="isImportant ? 'true' : 'false'"
					@click.prevent="hasWriteAcl ? onToggleImportant() : false"
					v-html="importantSvg" />
				<IconFavorite v-if="envelope.flags.flagged"
					fill-color="#f9cf3d"
					:size="18"
					class="app-content-list-item-star favorite-icon-style"
					:data-starred="envelope.flags.flagged ? 'true' : 'false'"
					@click.prevent="hasWriteAcl ? onToggleFlagged() : false" />
				<JunkIcon v-if="envelope.flags.$junk"
					:size="18"
					class="app-content-list-item-star junk-icon-style"
					:data-starred="envelope.flags.$junk ? 'true' : 'false'"
					@click.prevent="hasWriteAcl ? onToggleJunk() : false" />
			</div>

			<router-link :to="route"
				event=""
				class="left"
				:class="{seen: envelope.flags.seen}"
				@click.native.prevent="$emit('toggle-expand', $event)">
				<div class="envelope__header__left__sender-subject-tags">
					<div class="sender">
						{{ envelope.from && envelope.from[0] ? envelope.from[0].label : '' }}
						<p class="sender__email" :style="{ 'color': senderEmailColor }">
							{{ envelope.from && envelope.from[0] ? envelope.from[0].email : '' }}
						</p>
					</div>
					<div v-if="hasChangedSubject" class="subline">
						{{ cleanSubject }}
					</div>
					<div v-if="showSubline" class="subline">
						<span class="preview">
							{{ isEncrypted ? t('mail', 'Encrypted message') : envelope.previewText }}
						</span>
					</div>
					<div class="tagline">
						<div v-for="tag in tags"
							:key="tag.id"
							class="tag-group">
							<div class="tag-group__bg"
								:style="{'background-color': tag.color}" />
							<span class="tag-group__label"
								:style="{color: tag.color}">
								{{ translateTagDisplayName(tag) }}
							</span>
						</div>
					</div>
				</div>
				<div class="envelope__header__left__unsubscribe">
					<NcButton v-if="message && message.dkimValid && (message.unsubscribeUrl || message.unsubscribeMailto)"
						type="tertiary"
						class="envelope__header__unsubscribe"
						@click="showListUnsubscribeConfirmation = true">
						{{ t('mail', 'Unsubscribe') }}
					</NcButton>
				</div>
			</router-link>
			<div class="right">
				<Moment class="timestamp" :timestamp="envelope.dateInt" />
				<template v-if="expanded">
					<NcActions v-if="smimeData.isSigned || smimeData.isEncrypted">
						<template #icon>
							<LockPlusIcon v-if="smimeData.isEncrypted"
								:size="20"
								fill-color="#008000" />
							<LockIcon v-else-if="smimeData.signatureIsValid"
								:size="20"
								fill-color="#008000" />
							<LockOffIcon v-else
								:size="20"
								fill-color="red" />
						</template>
						<NcActionText class="smime-text" :name="smimeHeading">
							{{ smimeMessage }}
						</NcActionText>
						<!-- TODO: display information about signer and/or CA certificate -->
					</NcActions>
					<NcActions :inline="inlineMenuSize">
						<NcActionButton :close-after-click="true"
							@click="onReply('', false)">
							<template #icon>
								<ReplyAllIcon v-if="hasMultipleRecipients"
									:title="t('mail', 'Reply all')"
									:size="20" />
								<ReplyIcon v-else
									:title="t('mail', 'Reply')"
									:size="20" />
							</template>
							{{ t('mail', 'Reply') }}
						</NcActionButton>
						<NcActionButton v-if="hasMultipleRecipients"
							:close-after-click="true"
							@click="onReply('', false, true)">
							<template #icon>
								<ReplyIcon :title="t('mail', 'Reply to sender only')"
									:size="20" />
							</template>
							{{ t('mail', 'Reply to sender only') }}
						</NcActionButton>
						<NcActionButton v-if="hasWriteAcl && (inlineMenuSize >= 2 || !moreActionsOpen)"
							type="tertiary-no-background"
							class="action--primary"
							:aria-label="envelope.flags.flagged ? t('mail', 'Mark as unfavorite') : t('mail', 'Mark as favorite')"
							:close-after-click="true"
							@click.prevent="onToggleFlagged">
							<template #icon>
								<IconFavorite v-if="showFavoriteIconVariant"
									:title="t('mail', 'Mark as unfavorite')"
									:size="20" />
								<StarOutline v-else
									:title="t('mail', 'Mark as favorite')"
									:size="20" />
							</template>
							{{ envelope.flags.flagged ? t('mail', 'Mark as unfavorite') : t('mail', 'Mark as favorite') }}
						</NcActionButton>
						<NcActionButton v-if="hasSeenAcl && (inlineMenuSize >= 3 || !moreActionsOpen)"
							type="tertiary-no-background"
							class="action--primary"
							:aria-label="envelope.flags.seen ? t('mail', 'Mark as unread') : t('mail', 'Mark as read')"
							:close-after-click="true"
							@click.prevent="onToggleSeen">
							<template #icon>
								<EmailRead v-if="showImportantIconVariant"
									:title="t('mail', 'Mark as unread')"
									:size="20" />
								<EmailUnread v-else
									:title="t('mail', 'Mark as read')"
									:size="20" />
							</template>
							{{ envelope.flags.seen ? t('mail', 'Mark as unread') : t('mail', 'Mark as read') }}
						</NcActionButton>
						<NcActionButton v-if="showArchiveButton && hasArchiveAcl && (inlineMenuSize >= 4 || !moreActionsOpen)"
							:close-after-click="true"
							:disabled="disableArchiveButton"
							:aria-label="t('mail', 'Archive message')"
							type="tertiary-no-background"
							@click.prevent="onArchive">
							<template #icon>
								<ArchiveIcon :title="t('mail', 'Archive message')"
									:size="20" />
							</template>
							{{ t('mail', 'Archive message') }}
						</NcActionButton>
						<NcActionButton v-if="hasDeleteAcl && (inlineMenuSize >= 5 || !moreActionsOpen)"
							:close-after-click="true"
							:aria-label="t('mail', 'Delete message')"
							type="tertiary-no-background"
							@click.prevent="onDelete">
							<template #icon>
								<DeleteIcon :title="t('mail', 'Delete message')"
									:size="20" />
							</template>
							{{ t('mail', 'Delete message') }}
						</NcActionButton>
						<MenuEnvelope class="app-content-list-item-menu"
							:envelope="envelope"
							:mailbox="mailbox"
							:with-select="false"
							:with-show-source="true"
							:more-actions-open.sync="moreActionsOpen"
							@reply="onReply('', false, false)"
							@delete="$emit('delete',envelope.databaseId)"
							@show-source-modal="onShowSourceModal"
							@open-tag-modal="onOpenTagModal"
							@open-move-modal="onOpenMoveModal"
							@open-event-modal="onOpenEventModal"
							@open-task-modal="onOpenTaskModal"
							@open-translation-modal="onOpenTranslationModal"
							@open-mail-filter-from-envelope="showMailFilterFromEnvelope = true"
							@print="onPrint" />
					</NcActions>
					<SourceModal v-if="showSourceModal"
						:raw-message="rawMessage"
						@close="onCloseSourceModal" />
					<MoveModal v-if="showMoveModal"
						:account="account"
						:envelopes="[envelope]"
						@move="onMove"
						@close="onCloseMoveModal" />
					<EventModal v-if="showEventModal"
						:envelope="envelope"
						@close="onCloseEventModal" />
					<TaskModal v-if="showTaskModal"
						:envelope="envelope"
						@close="onCloseTaskModal" />
					<TagModal v-if="showTagModal"
						:account="account"
						:envelopes="[envelope]"
						@close="onCloseTagModal" />
					<TranslationModal v-if="showTranslationModal"
						:rich-parameters="{}"
						:message="plainTextBody"
						@close="onCloseTranslationModal" />
					<MailFilterFromEnvelope v-if="showMailFilterFromEnvelope"
						:account="account"
						:envelope="envelope"
						@close="showMailFilterFromEnvelope = false" />
				</template>
			</div>
		</div>
		<MessageLoadingSkeleton v-if="loading === Loading.Skeleton" />
		<Message v-if="message"
			v-show="loading === Loading.Done"
			:envelope="envelope"
			:message="message"
			:full-height="fullHeight"
			:smart-replies="showFollowUpHeader ? [] : smartReplies"
			:reply-button-label="replyButtonLabel"
			@load="onMessageLoaded"
			@translate="onOpenTranslationModal"
			@reply="(body) => onReply(body, showFollowUpHeader)" />
		<Error v-else-if="error"
			:error="error.message || t('mail', 'Not found')"
			message=""
			:data="error"
			:auto-margin="true"
			role="alert" />
		<ConfirmModal v-if="message && message.unsubscribeUrl && message.isOneClickUnsubscribe && showListUnsubscribeConfirmation"
			:confirm-text="t('mail', 'Unsubscribe')"
			:title="t('mail', 'Unsubscribe via link')"
			@cancel="showListUnsubscribeConfirmation = false"
			@confirm="unsubscribeViaOneClick">
			{{ t('mail', 'Unsubscribing will stop all messages from the mailing list {sender}', { sender: from }) }}
		</ConfirmModal>
		<ConfirmModal v-else-if="message && message.unsubscribeUrl && showListUnsubscribeConfirmation"
			:confirm-text="t('mail', 'Unsubscribe')"
			:confirm-url="message.unsubscribeUrl"
			:title="t('mail', 'Unsubscribe via link')"
			@cancel="showListUnsubscribeConfirmation = false"
			@confirm="showListUnsubscribeConfirmation = false">
			{{ t('mail', 'Unsubscribing will stop all messages from the mailing list {sender}', { sender: from }) }}
		</ConfirmModal>
		<ConfirmModal v-else-if="message && message.unsubscribeMailto && showListUnsubscribeConfirmation"
			:confirm-text="t('mail', 'Send unsubscribe email')"
			:title="t('mail', 'Unsubscribe via email')"
			:disabled="unsubscribing"
			@cancel="showListUnsubscribeConfirmation = false"
			@confirm="unsubscribeViaMailto">
			{{ t('mail', 'Unsubscribing will stop all messages from the mailing list {sender}', { sender: from }) }}
		</ConfirmModal>
	</div>
</template>
<script>
import Avatar from './Avatar.vue'
import { NcActionButton, NcButton } from '@nextcloud/vue'
import ConfirmModal from './ConfirmationModal.vue'
import Error from './Error.vue'
import importantSvg from '../../img/important.svg'
import IconFavorite from 'vue-material-design-icons/Star.vue'
import JunkIcon from './icons/JunkIcon.vue'
import MessageLoadingSkeleton from './MessageLoadingSkeleton.vue'
import logger from '../logger.js'
import Message from './Message.vue'
import MenuEnvelope from './MenuEnvelope.vue'
import Moment from './Moment.vue'
import { smartReply } from '../service/AiIntergrationsService.js'
import { mailboxHasRights } from '../util/acl.js'
import StarOutline from 'vue-material-design-icons/StarOutline.vue'
import DeleteIcon from 'vue-material-design-icons/TrashCanOutline.vue'
import ArchiveIcon from 'vue-material-design-icons/ArchiveArrowDownOutline.vue'
import EmailUnread from 'vue-material-design-icons/EmailOutline.vue'
import EmailRead from 'vue-material-design-icons/EmailOpenOutline.vue'
import LockIcon from 'vue-material-design-icons/LockOutline.vue'
import LockPlusIcon from 'vue-material-design-icons/LockPlusOutline.vue'
import LockOffIcon from 'vue-material-design-icons/LockOffOutline.vue'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder.js'
import { hiddenTags } from './tags.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { matchError } from '../errors/match.js'
import NoTrashMailboxConfiguredError from '../errors/NoTrashMailboxConfiguredError.js'
import { isPgpText } from '../crypto/pgp.js'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import ReplyIcon from 'vue-material-design-icons/ReplyOutline.vue'
import ReplyAllIcon from 'vue-material-design-icons/ReplyAllOutline.vue'
import { unsubscribe } from '../service/ListService.js'
import TagModal from './TagModal.vue'
import MoveModal from './MoveModal.vue'
import TaskModal from './TaskModal.vue'
import EventModal from './EventModal.vue'
import TranslationModal from './TranslationModal.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import useOutboxStore from '../store/outboxStore.js'
import moment from '@nextcloud/moment'
import { translateTagDisplayName } from '../util/tag.js'
import { FOLLOW_UP_TAG_LABEL } from '../store/constants.js'
import { Text, toPlain } from '../util/text.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'
import MailFilterFromEnvelope from './mailFilter/MailFilterFromEnvelope.vue'
import SourceModal from './SourceModal.vue'

// Ternary loading state
const Loading = Object.seal({
	Done: 0,
	Silent: 1,
	Skeleton: 2,
})

export default {
	name: 'ThreadEnvelope',
	components: {
		MailFilterFromEnvelope,
		EventModal,
		TaskModal,
		MoveModal,
		TagModal,
		TranslationModal,
		ConfirmModal,
		Avatar,
		NcActionButton,
		NcButton,
		Error,
		IconFavorite,
		JunkIcon,
		MessageLoadingSkeleton,
		MenuEnvelope,
		Moment,
		Message,
		StarOutline,
		EmailRead,
		EmailUnread,
		DeleteIcon,
		ArchiveIcon,
		LockIcon,
		LockOffIcon,
		LockPlusIcon,
		NcActions,
		NcActionText,
		ReplyIcon,
		ReplyAllIcon,
		SourceModal,
	},
	props: {
		envelope: {
			required: true,
			type: Object,
		},
		mailboxId: {
			required: false,
			type: [
				String,
				Number,
			],
			default: undefined,
		},
		expanded: {
			required: false,
			type: Boolean,
			default: false,
		},
		fullHeight: {
			required: false,
			type: Boolean,
			default: false,
		},
		withSelect: {
			// "Select" action should only appear in envelopes from the envelope list
			type: Boolean,
			default: true,
		},
		threadSubject: {
			required: true,
			type: String,
		},
		threadIndex: {
			required: true,
			type: Number,
		},
	},
	data() {
		return {
			loading: Loading.Done,
			showListUnsubscribeConfirmation: false,
			error: undefined,
			message: undefined,
			importantSvg,
			unsubscribing: false,
			seenTimer: undefined,
			Loading,
			recomputeMenuSize: 0,
			moreActionsOpen: false,
			smartReplies: [],
			showSourceModal: false,
			showMoveModal: false,
			showEventModal: false,
			showTaskModal: false,
			showTagModal: false,
			showTranslationModal: false,
			plainTextBody: '',
			rawMessage: '', // Will hold the raw source of the message when requested
			isInternal: true,
			enabledFreePrompt: loadState('mail', 'llm_freeprompt_available', false),
			loadingBodyTimeout: undefined,
			showMailFilterFromEnvelope: false,
		}
	},
	computed: {
		...mapStores(useOutboxStore, useMainStore),
		inlineMenuSize() {
			// eslint-disable-next-line no-unused-expressions
			const { envelope } = this.$refs
			const envelopeWidth = (envelope && envelope.clientWidth) || 250
			const spaceToFill = envelopeWidth - 500 + this.recomputeMenuSize
			return Math.floor(spaceToFill / 44)
		},
		account() {
			return this.mainStore.getAccount(this.envelope.accountId)
		},
		senderEmailColor() {
			if (this.isInternal) {
				return 'var(--color-text-maxcontrast)'
			}

			return parseInt(this.mainStore.getNcVersion) >= 32 ? 'var(--color-text-error)' : 'var(--color-error)'
		},
		from() {
			if (!this.message || !this.message.from.length) {
				return '?'
			}
			if (this.message.from[0].label) {
				return this.message.from[0].label
			}
			return this.message.from[0].email
		},
		hasMultipleRecipients() {
			if (!this.account) {
				console.error('account is undefined', {
					accountId: this.envelope.accountId,
				})
			}
			const recipients = buildReplyRecipients(this.envelope, {
				label: this.account.name,
				email: this.account.emailAddress,
			})
			return recipients.to.concat(recipients.cc).length > 1
		},
		route() {
			return {
				name: 'message',
				params: {
					mailboxId: this.mailboxId || this.envelope.mailboxId,
					threadId: this.envelope.databaseId,
				},
			}
		},
		isEncrypted() {
			return this.envelope.previewText
				&& isPgpText(this.envelope.previewText)
		},
		isImportant() {
			return this.mainStore
				.getEnvelopeTags(this.envelope.databaseId)
				.find((tag) => tag.imapLabel === '$label1')
		},
		tags() {
			return this.mainStore.getEnvelopeTags(this.envelope.databaseId).filter(
				(tag) => tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags),
			)
		},
		hasChangedSubject() {
			return this.cleanSubject !== this.cleanThreadSubject
		},
		cleanSubject() {
			return this.filterSubject(this.envelope.subject)
		},
		cleanThreadSubject() {
			return this.filterSubject(this.threadSubject)
		},
		showSubline() {
			return !this.expanded && !!this.envelope.previewText
		},
		showArchiveButton() {
			return this.account.archiveMailboxId !== null
		},
		disableArchiveButton() {
			return this.account.archiveMailboxId !== null
				&& this.account.archiveMailboxId === this.mailbox.databaseId
		},
		junkFavoritePosition() {
			return this.showSubline && this.tags.length > 0
		},
		showFavoriteIconVariant() {
			return this.envelope.flags.flagged
		},
		showImportantIconVariant() {
			return this.envelope.flags.seen
		},
		hasSeenAcl() {
			return mailboxHasRights(this.mailbox, 's')
		},
		hasArchiveAcl() {
			const hasDeleteSourceAcl = () => {
				return mailboxHasRights(this.mailbox, 'te')
			}

			const hasCreateDestinationAcl = () => {
				return mailboxHasRights(this.archiveMailbox, 'i')
			}

			return hasDeleteSourceAcl() && hasCreateDestinationAcl()
		},
		hasDeleteAcl() {
			return mailboxHasRights(this.mailbox, 'te')
		},
		hasWriteAcl() {
			return mailboxHasRights(this.mailbox, 'w')
		},
		mailbox() {
			return this.mainStore.getMailbox(this.mailboxId)
		},
		archiveMailbox() {
			return this.mainStore.getMailbox(this.account.archiveMailboxId)
		},
		/**
		 * @return {{isSigned: (boolean|undefined), signatureIsValid: (boolean|undefined)}}
		 */
		smimeData() {
			return this.message?.smime ?? {}
		},
		smimeHeading() {
			if (this.smimeData.isEncrypted) {
				return t('mail', 'Encrypted & verified ')
			}

			if (this.smimeData.signatureIsValid) {
				return t('mail', 'Signature verified')
			}

			return t('mail', 'Signature unverified ')
		},
		smimeMessage() {
			if (this.smimeData.isEncrypted) {
				return t('mail', 'This message was encrypted by the sender before it was sent.')
			}

			if (this.smimeData.signatureIsValid) {
				return t('mail', 'This message contains a verified digital S/MIME signature. The message wasn\'t changed since it was sent.')
			}

			return t('mail', 'This message contains an unverified digital S/MIME signature. The message might have been changed since it was sent or the certificate of the signer is untrusted.')
		},
		/**
		 * A human readable representation of envelope's sent date (without the time).
		 *
		 * @return {string}
		 */
		formattedSentAt() {
			return moment(this.envelope.dateInt * 1000).format('LL')
		},
		/**
		 * @return {boolean}
		 */
		showFollowUpHeader() {
			const tags = this.mainStore.getEnvelopeTags(this.envelope.databaseId)
			return tags.some((tag) => tag.imapLabel === FOLLOW_UP_TAG_LABEL)
		},
		/**
		 * Translated label for the reply button.
		 *
		 * @return {string}
		 */
		replyButtonLabel() {
			if (this.showFollowUpHeader) {
				return t('mail', 'Follow up')
			}

			if (this.hasMultipleRecipients) {
				return t('mail', 'Reply all')
			}

			return t('mail', 'Reply')
		},
	},
	watch: {
		expanded(expanded) {
			if (expanded) {
				this.fetchMessage()
			} else {
				this.message = undefined
				this.loading = Loading.Done
			}
		},
		loading(loading) {
			if (loading === Loading.Done) {
				this.$emit('loaded')
			}
		},
	},
	async mounted() {
		window.addEventListener('resize', this.redrawMenuBar)
		if (this.expanded) {
			await this.fetchMessage()

			// Only one envelope is expanded at the time of mounting so we can
			// assume that this is the relevant envelope to be scrolled to.
			this.$nextTick(() => this.handleThreadScrolling())
		}
		if (this.mainStore.getPreference('internal-addresses', 'false') === 'true') {
			this.isInternal = this.mainStore.isInternalAddress(this.envelope.from[0].email)
		}
		this.$checkInterval = setInterval(() => {
			const { envelope } = this.$refs
			const isWidthAvailable = (envelope && envelope.clientWidth > 0)
			if (isWidthAvailable) {
				this.redrawMenuBar()
				clearInterval(this.$checkInterval)
			}
		}, 100)
	},
	beforeDestroy() {
		if (this.seenTimer !== undefined) {
			logger.info('Navigating away before seenTimer delay, will not mark message as seen/read')
			clearTimeout(this.seenTimer)
		}
		window.removeEventListener('resize', this.redrawMenuBar)
	},
	methods: {
		translateTagDisplayName,
		redrawMenuBar() {
			this.$nextTick(() => {
				this.recomputeMenuSize++
			})
		},
		filterSubject(value) {
			return value.replace(/((?:[\t ]*(?:R|RE|F|FW|FWD):[\t ]*)*)/i, '')
		},
		onMessageLoaded() {
			if (this.loadingBodyTimeout) {
				clearTimeout(this.loadingBodyTimeout)
				this.loadingBodyTimeout = undefined
			}

			this.loading = Loading.Done
		},
		async fetchMessage() {
			let loadingTimeout
			const isCached = !!this.mainStore.getMessage(this.envelope.databaseId)
			if (!isCached) {
				loadingTimeout = setTimeout(() => {
					this.loading = Loading.Skeleton
				}, 200)
			}

			this.loading = Loading.Silent
			this.error = undefined
			logger.debug(`fetching thread message ${this.envelope.databaseId}`)

			try {
				this.message = await this.mainStore.fetchMessage(this.envelope.databaseId)
				logger.debug(`message ${this.envelope.databaseId} fetched`, { message: this.message })

				if (loadingTimeout) {
					clearTimeout(loadingTimeout)
				}

				if (!this.envelope.flags.seen && this.hasSeenAcl) {
					logger.info('Starting timer to mark message as seen/read')
					this.seenTimer = setTimeout(() => {
						this.mainStore.toggleEnvelopeSeen({ envelope: this.envelope })
						this.seenTimer = undefined
					}, 2000)
				}

				if (this.message.hasHtmlBody) {
					this.loadingBodyTimeout = setTimeout(() => {
						this.loading = Loading.Skeleton
					}, 200)
				} else {
					this.loading = Loading.Done
				}
				this.$nextTick(() => {
					this.handleThreadScrolling()
				})
			} catch (error) {
				this.error = error
				this.loading = Loading.Done
				logger.error('Could not fetch message', { error })
			}

			// Fetch itineraries if they haven't been included in the message data
			if (this.message && !this.message.itineraries) {
				this.fetchItineraries()
			}
			// Fetch dkim
			if (this.message && this.message.dkimValid === undefined) {
				this.fetchDkim()
			}

			// Fetch smart replies
			if (this.enabledFreePrompt && this.message && !['trash', 'junk'].includes(this.mailbox.specialRole) && !this.showFollowUpHeader) {
				this.smartReplies = await smartReply(this.envelope.databaseId)
			}
		},
		handleThreadScrolling() {
			const threadId = this.envelope.threadId // Assuming each envelope has a thread ID

			if (threadId && this.$parent.toggleExpand) {
				// If thread is not expanded, expand it first
				if (!this.$parent.expandedThreads.includes(threadId)) {
					this.$parent.toggleExpand(threadId)
					this.$nextTick(() => this.scrollToThread(threadId))
				} else {
					this.scrollToThread(threadId)
				}
			} else {
				// If there's no thread, just scroll to the envelope
				this.scrollToEnvelope()
			}
		},
		scrollToThread(threadId) {
			this.$nextTick(() => {
				const threadElement = document.querySelector(`[data-thread-id="${threadId}"]`)
				if (threadElement) {
					threadElement.scrollIntoView({ behavior: 'smooth', block: 'top' })
				}
			})
		},

		scrollToEnvelope() {
			this.$nextTick(() => {
				const envelopeElement = this.$refs.envelope
				if (envelopeElement) {
					envelopeElement.scrollIntoView({ behavior: 'smooth', block: 'top' })
				}
			})
		},
		async fetchItineraries() {
			// Sanity check before actually making the request
			if (!this.message.hasHtmlBody && this.message.attachments.length === 0) {
				return
			}

			logger.debug(`Fetching itineraries for message ${this.envelope.databaseId}`)

			try {
				const itineraries = await this.mainStore.fetchItineraries(this.envelope.databaseId)
				logger.debug(`Itineraries of message ${this.envelope.databaseId} fetched`, { itineraries })
			} catch (error) {
				logger.error(`Could not fetch itineraries of message ${this.envelope.databaseId}`, { error })
			}
		},
		async fetchDkim() {
			if (this.message.hasDkimSignature === false) {
				return
			}

			logger.debug(`Fetching DKIM for message ${this.envelope.databaseId}`)

			try {
				const dkim = await this.mainStore.fetchDkim(this.envelope.databaseId)
				logger.debug(`DKIM of message ${this.envelope.databaseId} fetched`, { dkim })
			} catch (error) {
				logger.error(`Could not fetch DKIM of message ${this.envelope.databaseId}`, { error })
			}
		},
		onReply(body = '', followUp = false, replySenderOnly = false) {
			this.mainStore.startComposerSession({
				reply: {
					mode: (this.hasMultipleRecipients && !replySenderOnly) ? 'replyAll' : 'reply',
					data: this.envelope,
					smartReply: body,
					followUp,
				},
			})
		},
		onToggleImportant() {
			this.mainStore.toggleEnvelopeImportant(this.envelope)
		},
		onToggleFlagged() {
			this.mainStore.toggleEnvelopeFlagged(this.envelope)
		},
		onToggleJunk() {
			this.mainStore.toggleEnvelopeJunk(this.envelope)
		},
		onToggleSeen() {
			this.mainStore.toggleEnvelopeSeen({ envelope: this.envelope })
		},
		async onDelete() {
			// Remove from selection first
			if (this.withSelect) {
				this.$emit('unselect')
			}

			// Delete
			this.$emit('delete', this.envelope.databaseId)

			logger.info(`deleting message ${this.envelope.databaseId}`)

			try {
				await this.mainStore.deleteMessage({
					id: this.envelope.databaseId,
				})
			} catch (error) {
				showError(await matchError(error, {
					[NoTrashMailboxConfiguredError.getName()]() {
						return t('mail', 'No trash folder configured')
					},
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			}
		},
		async onArchive() {
			// Remove from selection first
			if (this.withSelect) {
				this.$emit('unselect')
			}

			// Archive
			this.$emit('archive', this.envelope.databaseId)

			logger.info(`archiving message ${this.envelope.databaseId}`)

			try {
				await this.mainStore.moveMessage({
					id: this.envelope.databaseId,
					destMailboxId: this.account.archiveMailboxId,
				})
			} catch (error) {
				logger.error('could not archive message', error)
				return t('mail', 'Could not archive message')
			}
		},
		async onDisableFollowUpReminder() {
			await this.mainStore.clearFollowUpReminder({
				envelope: this.envelope,
			})
		},
		async unsubscribeViaOneClick() {
			try {
				this.unsubscribing = true

				await unsubscribe(this.envelope.databaseId)
				showSuccess(t('mail', 'Unsubscribe request sent'))
			} catch (error) {
				logger.error('Could not one-click unsubscribe', { error })
				showError(t('mail', 'Could not unsubscribe from mailing list'))
			} finally {
				this.unsubscribing = false
				this.showListUnsubscribeConfirmation = false
			}
		},
		async unsubscribeViaMailto() {
			const mailto = this.message.unsubscribeMailto
			const [email, paramString] = mailto.replace(/^mailto:/, '').split('?')
			let params = {}
			const now = new Date().getTime() / 1000
			if (paramString) {
				params = paramString.split('&').map(encoded => ({
					key: encoded.split('=')[0].toLowerCase(),
					value: decodeURIComponent(encoded.split('=')[1]),
				}))
			}
			try {
				this.unsubscribing = true
				const message = await this.outboxStore.enqueueMessage({
					message: {
						accountId: this.message.accountId,
						subject: params.subject || 'Unsubscribe',
						body: params.body || '',
						editorBody: params.body || '',
						isHtml: false,
						to: [{
							label: email,
							email,
						}],
						cc: [],
						bcc: [],
						attachments: [],
						aliasId: null,
						inReplyToMessageId: null,
						sendAt: now,
						draftId: null,
						smimeEncrypt: false,
						smimeSign: false,
					},
				})
				logger.debug('Unsubscribe email to ' + email + ' enqueued')
				await this.outboxStore.sendMessage({ id: message.id })
				logger.debug('Unsubscribe email sent to ' + email)
				showSuccess(t('mail', 'Unsubscribe request sent'))
			} catch (error) {
				logger.error('Could not enqueue or send unsubscribe email', { error })
				showError(t('mail', 'Could not unsubscribe from mailing list'))
			} finally {
				this.unsubscribing = false
				this.showListUnsubscribeConfirmation = false
			}
		},
		onMove() {
			this.$emit('move')
		},
		onOpenMoveModal() {
			this.showMoveModal = true
		},
		onCloseMoveModal() {
			this.showMoveModal = false
		},
		onOpenEventModal() {
			this.showEventModal = true
		},
		onCloseEventModal() {
			this.showEventModal = false
		},
		onOpenTaskModal() {
			this.showTaskModal = true
		},
		onCloseTaskModal() {
			this.showTaskModal = false
		},
		onOpenTagModal() {
			this.showTagModal = true
		},
		onCloseTagModal() {
			this.showTagModal = false
		},
		onOpenTranslationModal() {
			try {
				if (this.message.hasHtmlBody) {
					let text = new Text('html', this.message.body)
					text = toPlain(text)
					this.plainTextBody = text.value
				} else {
					this.plainTextBody = this.message.body
				}
				this.showTranslationModal = true
			} catch (error) {
				showError(t('mail', 'Please wait for the message to load'))
			}
		},
		onCloseTranslationModal() {
			this.showTranslationModal = false
		},
		async onShowSourceModal() {
			if (this.rawMessage.length === 0) {
				const resp = await axios.get(
					generateUrl('/apps/mail/api/messages/{id}/source', {
						id: this.envelope.databaseId,
					}),
				)
				this.rawMessage = resp.data.source
			}
			this.showSourceModal = true
		},
		onCloseSourceModal() {
			this.showSourceModal = false
		},
		onPrint() {
			this.$emit('print', this.threadIndex)
		},
	},
}
</script>

<style lang="scss" scoped>
	.sender {
		margin-inline-start: calc(var(--default-grid-baseline) * 2);
		&__email{
			text-overflow: ellipsis;
			overflow: hidden;
		}

	}

	.right {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
		margin-inline-start: calc(var(--default-grid-baseline) * 2);
		height: 44px;

		.app-content-list-item-menu {
			margin-inline-start: var(--default-grid-baseline);
		}

		.timestamp {
			margin-inline-end: calc(var(--default-grid-baseline) * 2);
			color: var(--color-text-maxcontrast);
			white-space: nowrap;
			margin-bottom: 0;
		}
	}

	.button {
		color: var(--color-main-background);
		&:not(.active):not(.primary) {
			display: none;

			&.primary {
				background-color: var(--color-primary-element);
				opacity: 1;
				margin-bottom: 0;

			}
		}
	}

	.envelope {
		display: flex;
		flex-direction: column;
		border: 2px solid var(--color-border);
		border-radius: var(--border-radius-container-large);
		margin-inline: calc(var(--default-grid-baseline) * 2);
		background-color: var(--color-main-background);
		padding-bottom: calc(var(--default-grid-baseline) * 7);
		animation: show 200ms 90ms cubic-bezier(.17, .67, .83, .67) forwards;
		opacity: 0.5;
		transform-origin: top center;
		@keyframes show {
			100% {
				opacity: 1;
				transform: none;
			}
		}

		& + .envelope {
			margin-top: calc(var(--default-grid-baseline) * -7);
		}

		&:last-of-type {
			margin-bottom: calc(var(--default-grid-baseline) * 2);
			padding-bottom: 0;
		}

		&__follow-up-header {
			display: flex;
			align-items: center;
			justify-content: flex-end;
			gap: calc(var(--default-grid-baseline) * 4);
			padding: calc(var(--default-grid-baseline) * 2);

			&__date {
				flex-shrink: 1;
			}

			&__actions {
				flex-shrink: 0;
				display: flex;
				gap: var(--default-grid-baseline);
			}
		}

		&__header {
			position: relative;
			display: flex;
			align-items: center;
			padding: var(--border-radius-element) var(--border-radius-container) var(--border-radius-container) var(--border-radius-container);
			border-radius: var(--border-radius);
			min-height: 68px; /* prevents jumping between open/collapsed */

			&__avatar {
				position: relative;

				&-avatar {
					/* The block makes the wrapper div cover the avatar exactly
					 * (no extra space) and allows center aligning the avatar
					 * with the rest of the header elements.
					 */
					display: block;
				}

				.app-content-list-item-star {
					position: absolute;
					cursor: pointer;

					&.icon-important {
						background-image: none;
						opacity: 1;
						width: 16px;
						height: 16px;
						display: flex;
						top: 0px;
						inset-inline-start: 0px;

						&:hover,
						&:focus {
							opacity: 0.5;
						}

						:deep(path) {
							fill: #ffcc00;
							stroke: var(--color-main-background);
							cursor: pointer;
						}
					}
					&.favorite-icon-style {
						display: inline-block;
						top: -2px;
						inset-inline-end: -2px;

						stroke: var(--color-main-background);
						stroke-width: 2;
						&:hover {
							opacity: .5;
						}
					}
					&.junk-icon-style {
						display: inline-block;
						bottom: -2px;
						inset-inline-end: -2px;
						opacity: .2;
						&:hover {
							opacity: .1;
						}
					}
				}
			}

			&__unsubscribe {
				color: var(--color-text-maxcontrast);
			}
			&__left__sender-subject-tags {
				white-space: nowrap;
				width: 100%;
			}
		}

		.subline {
			margin-inline-start: 8px;
			color: var(--color-text-maxcontrast);
			cursor: default;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		&--expanded {
			min-height: 350px;
		}
	}

	.left {
		flex-grow: 1;
		min-width: 0; /* https://css-tricks.com/flexbox-truncated-text/ */
		display: flex;
		position: relative;
		z-index: 1;
		align-items: center;
	}

	.left:not(.seen) {
		font-weight: bold;
	}

	.tag-group__label {
		margin: 0 calc(var(--default-grid-baseline) * 2);
		z-index: 2;
		font-size: calc(var(--default-font-size) * 0.8);
		font-weight: bold;
		padding-inline: calc(var(--default-grid-baseline) * 0.5);
	}

	.tag-group__bg {
		position: absolute;
		width: 100%;
		height: 100%;
		top: 0;
		inset-inline-start: 0;
		opacity: 15%;
	}

	.tagline {
		display: flex;
		text-overflow: ellipsis;
		overflow: hidden;
	}

	.tag-group {
		display: inline-block;
		border: 1px solid transparent;
		border-radius: var(--border-radius-pill);
		position: relative;
		margin: 0 1px;
		overflow: hidden;
		text-overflow: ellipsis;
		inset-inline-start: var(--default-grid-baseline);
	}

	.smime-text {
		// same as padding-right on action-text styling
		padding-inline-start: calc(var(--default-grid-baseline) * 3);
	}

	:deep(.action-button__name) {
		font-weight: normal;
		display: inline;
		align-items: center;
	}
	@media only screen and (max-width: 400px) {
		.sender {
			text-overflow: ellipsis;
			overflow: hidden;
			width: 180px;
		}
	}
</style>
