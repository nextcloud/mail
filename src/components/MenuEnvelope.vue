<!-- Standard Actions menu for Envelopes -->
<template>
	<div>
		<Actions
			menu-align="right"
			event=""
			@click.native.prevent>
			<template v-if="!moreActionsOpen">
				<EnvelopePrimaryActions>
					<ActionButton :icon="iconFavorite"
						class="action--primary"
						:close-after-click="true"
						@click.prevent="onToggleFlagged">
						{{
							envelope.flags.flagged ? t('mail', 'Unfavorite') : t('mail', 'Favorite')
						}}
					</ActionButton>
					<ActionButton icon="icon-mail"
						class="action--primary"
						:close-after-click="true"
						@click.prevent="onToggleSeen">
						{{
							envelope.flags.seen ? t('mail', 'Unread') : t('mail', 'Read')
						}}
					</ActionButton>
					<ActionButton icon="icon-important"
						class="action--primary"
						:close-after-click="true"
						@click.prevent="onToggleImportant">
						{{
							isImportant ? t('mail', 'Unimportant') : t('mail', 'Important')
						}}
					</ActionButton>
				</EnvelopePrimaryActions>
				<ActionButton v-if="withReply"
					:icon="hasMultipleRecipients ? 'icon-reply-all' : 'icon-reply'"
					:close-after-click="true"
					@click="onReply">
					{{ t('mail', 'Reply') }}
				</ActionButton>
				<ActionButton v-if="hasMultipleRecipients"
					icon="icon-reply"
					:close-after-click="true"
					@click="onReply">
					{{ t('mail', 'Reply to sender only') }}
				</ActionButton>
				<ActionButton icon="icon-forward"
					:close-after-click="true"
					@click="onForward">
					{{ t('mail', 'Forward') }}
				</ActionButton>
				<ActionButton icon="icon-junk"
					:close-after-click="true"
					@click.prevent="onToggleJunk">
					{{
						envelope.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
					}}
				</ActionButton>
				<ActionButton
					icon="icon-tag"
					:close-after-click="true"
					@click.prevent="onOpenTagModal">
					{{ t('mail', 'Edit tags') }}
				</ActionButton>
				<ActionButton v-if="withSelect"
					icon="icon-checkmark"
					:close-after-click="true"
					@click.prevent="toggleSelected">
					{{
						isSelected ? t('mail', 'Unselect') : t('mail', 'Select')
					}}
				</ActionButton>
				<ActionButton icon="icon-external"
					:close-after-click="true"
					@click.prevent="onOpenMoveModal">
					{{ t('mail', 'Move message') }}
				</ActionButton>
				<ActionButton icon="icon-more"
					:close-after-click="false"
					@click="moreActionsOpen=true">
					{{ t('mail', 'More actions') }}
				</ActionButton>
				<ActionButton icon="icon-delete"
					:close-after-click="true"
					@click.prevent="onDelete">
					{{ t('mail', 'Delete message') }}
				</ActionButton>
			</template>
			<template v-if="moreActionsOpen">
				<ActionButton :close-after-click="false"
					@click="moreActionsOpen=false">
					<template #icon>
						<ChevronLeft
							:title="t('mail', 'More actions')"
							:size="20" />
						{{ t('mail', 'More actions') }}
					</template>
				</ActionButton>
				<ActionButton
					icon="icon-forward"
					:close-after-click="true"
					@click.prevent="forwardSelectedAsAttachment">
					{{ t('mail', 'Forward message as attachment') }}
				</ActionButton>
				<ActionButton icon="icon-add"
					:close-after-click="true"
					@click="onOpenEditAsNew">
					{{ t('mail', 'Edit as new message') }}
				</ActionButton>
				<ActionButton icon="icon-calendar-dark"
					:close-after-click="true"
					@click.prevent="showEventModal = true">
					{{ t('mail', 'Create event') }}
				</ActionButton>
				<ActionButton v-if="withShowSource"
					:icon="sourceLoading ? 'icon-loading-small' : 'icon-details'"
					:disabled="sourceLoading"
					:close-after-click="true"
					@click.prevent="onShowSourceModal">
					{{ t('mail', 'View source') }}
				</ActionButton>
				<ActionLink v-if="debug"
					icon="icon-download"
					:download="threadingFileName"
					:href="threadingFile"
					:close-after-click="true">
					{{ t('mail', 'Download thread data for debugging') }}
				</ActionLink>
			</template>
		</Actions>
		<Modal v-if="showSourceModal" class="source-modal" @close="onCloseSourceModal">
			<div class="source-modal-content">
				<div class="section">
					<h2>{{ t('mail', 'Message source') }}</h2>
					<pre class="message-source">{{ rawMessage }}</pre>
				</div>
			</div>
		</Modal>
		<MoveModal v-if="showMoveModal"
			:account="account"
			:envelopes="[envelope]"
			@move="onMove"
			@close="onCloseMoveModal" />
		<EventModal v-if="showEventModal"
			:envelope="envelope"
			@close="showEventModal = false" />
		<TagModal
			v-if="showTagModal"
			:account="account"
			:envelope="envelope"
			@close="onCloseTagModal" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import { Base64 } from 'js-base64'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'
import EventModal from './EventModal'
import EnvelopePrimaryActions from './EnvelopePrimaryActions'
import { generateUrl } from '@nextcloud/router'
import logger from '../logger'
import { matchError } from '../errors/match'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import TagModal from './TagModal'
import MoveModal from './MoveModal'
import NoTrashMailboxConfiguredError from '../errors/NoTrashMailboxConfiguredError'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'MenuEnvelope',
	components: {
		Actions,
		ActionButton,
		ActionLink,
		ChevronLeft,
		EventModal,
		Modal,
		MoveModal,
		TagModal,
		EnvelopePrimaryActions,
	},
	props: {
		envelope: {
			// The envelope on which this menu will act
			type: Object,
			required: true,
		},
		mailbox: {
			// It is just used to get the accountId when envelope doesn't have it
			type: Object,
			required: false,
			default: undefined,
		},
		isSelected: {
			// Indicates if the envelope is currently selected
			type: Boolean,
			default: false,
		},
		withReply: {
			// "Reply" action should only appear in envelopes from the envelope list
			// (Because in thread envelopes, this action is already set as primary button of this menu)
			type: Boolean,
			default: true,
		},
		withSelect: {
			// "Select" action should only appear in envelopes from the envelope list
			type: Boolean,
			default: true,
		},
		withShowSource: {
			// "Show source" action should only appear in thread envelopes
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			debug: window?.OC?.debug || false,
			rawMessage: '', // Will hold the raw source of the message when requested
			sourceLoading: false,
			showSourceModal: false,
			showMoveModal: false,
			showEventModal: false,
			showTagModal: false,
			moreActionsOpen: false,
			forwardMessages: this.envelope.databaseId,
		}
	},
	computed: {
		account() {
			const accountId = this.envelope.accountId ?? this.mailbox.accountId
			return this.$store.getters.getAccount(accountId)
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
		threadingFile() {
			return `data:text/plain;base64,${Base64.encode(JSON.stringify({
				subject: this.envelope.subject,
				messageId: this.envelope.messageId,
				inReplyTo: this.envelope.inReplyTo,
				references: this.envelope.references,
				threadRootId: this.envelope.threadRootId,
			}, null, 2))}`
		},
		threadingFileName() {
			return `${this.envelope.databaseId}.json`
		},
		iconFavorite() {
			return this.envelope.flags.flagged ? 'icon-favorite' : 'icon-starred'
		},
		isImportant() {
			return this.$store.getters
				.getEnvelopeTags(this.envelope.databaseId)
				.some((tag) => tag.imapLabel === '$label1')
		},
	},
	methods: {
		onForward() {
			this.$store.dispatch('showMessageComposer', {
				reply: {
					mode: 'forward',
					data: this.envelope,
				},
			})
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.envelope)
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.envelope)
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', { envelope: this.envelope })
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.envelope)
		},
		toggleSelected() {
			this.$emit('update:selected')
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
				await this.$store.dispatch('deleteMessage', {
					id: this.envelope.databaseId,
				})
			} catch (error) {
				showError(await matchError(error, {
					[NoTrashMailboxConfiguredError.getName()]() {
						return t('mail', 'No trash mailbox configured')
					},
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			}
		},
		async forwardSelectedAsAttachment() {
			this.forwardedMessages = [this.envelope.databaseId]
			await this.$store.dispatch('showMessageComposer', {
				forwardedMessages: this.forwardedMessages,
			})
		},
		async onShowSourceModal() {
			this.sourceLoading = true

			try {
				const resp = await axios.get(
					generateUrl('/apps/mail/api/messages/{id}/source', {
						id: this.envelope.databaseId,
					})
				)

				this.rawMessage = resp.data.source
				this.showSourceModal = true
			} finally {
				this.sourceLoading = false
			}
		},
		onCloseSourceModal() {
			this.showSourceModal = false
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
		onOpenTagModal() {
			this.showTagModal = true
		},
		onReply() {
			this.$store.dispatch('showMessageComposer', {
				reply: {
					mode: this.hasMultipleRecipients ? 'replyAll' : 'reply',
					data: this.envelope,
				},
			})
		},
		onCloseTagModal() {
			this.showTagModal = false
		},
		async onOpenEditAsNew() {
			await this.$store.dispatch('showMessageComposer', {
				templateMessageId: this.envelope.databaseId,
				data: this.envelope,
			})
		},
	},
}
</script>
<style lang="scss" scoped>
	.source-modal {
		::v-deep .modal-container {
			height: 800px;
		}

		.source-modal-content {
			width: 100%;
			height: 100%;
			overflow-y: scroll !important;
		}
	}

</style>
