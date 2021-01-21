<!-- Standard Actions menu for Envelopes -->
<template>
	<div>
		<Actions
			menu-align="right"
			event=""
			@click.native.prevent>
			<ActionRouter v-if="hasMultipleRecipients"
				icon="icon-reply"
				:close-after-click="true"
				:to="replyOneLink">
				{{ t('mail', 'Reply to sender only') }}
			</ActionRouter>
			<ActionRouter icon="icon-forward"
				:close-after-click="true"
				:to="forwardLink">
				{{ t('mail', 'Forward') }}
			</ActionRouter>
			<ActionRouter icon="icon-add"
				:to="{
					name: 'message',
					params: {
						mailboxId: $route.params.mailboxId,
						threadId: 'asNew',
						filter: $route.params.filter,
					},
					query: {
						messageId: envelope.databaseId,
					},
				}">
				{{ t('mail', 'Edit as new message') }}
			</ActionRouter>
			<ActionButton icon="icon-important"
				:close-after-click="true"
				@click.prevent="onToggleImportant">
				{{
					envelope.flags.important ? t('mail', 'Mark unimportant') : t('mail', 'Mark important')
				}}
			</ActionButton>
			<ActionButton :icon="iconFavorite"
				:close-after-click="true"
				@click.prevent="onToggleFlagged">
				{{
					envelope.flags.flagged ? t('mail', 'Mark unfavorite') : t('mail', 'Mark favorite')
				}}
			</ActionButton>
			<ActionButton icon="icon-mail"
				:close-after-click="true"
				@click.prevent="onToggleSeen">
				{{
					envelope.flags.seen ? t('mail', 'Mark unread') : t('mail', 'Mark read')
				}}
			</ActionButton>
			<ActionButton icon="icon-junk"
				:close-after-click="true"
				@click.prevent="onToggleJunk">
				{{
					envelope.flags.junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
				}}
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
				{{ t('mail', 'Move') }}
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
			<ActionButton icon="icon-delete"
				:close-after-click="true"
				@click.prevent="onDelete">
				{{ t('mail', 'Delete') }}
			</ActionButton>
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
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import ActionRouter from '@nextcloud/vue/dist/Components/ActionRouter'
import { Base64 } from 'js-base64'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'
import { generateUrl } from '@nextcloud/router'
import logger from '../logger'
import { matchError } from '../errors/match'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import MoveModal from './MoveModal'
import NoTrashMailboxConfiguredError from '../errors/NoTrashMailboxConfiguredError'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'MenuEnvelope',
	components: {
		Actions,
		ActionButton,
		ActionLink,
		ActionRouter,
		Modal,
		MoveModal,
	},
	props: {
		envelope: { // The envelope on which this menu will act
			type: Object,
			required: true,
		},
		mailbox: { // It is just used to get the accountId when envelope doesn't have it
			type: Object,
			required: false,
			default: undefined,
		},
		isSelected: { // Indicates if the envelope is currently selected
			type: Boolean,
			default: false,
		},
		withSelect: { // "Select" action should only appear in envelopes from the envelope list
			type: Boolean,
			default: true,
		},
		withShowSource: { // "Show source" action should only appear in thread envelopes
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
		replyOneLink() {
			return {
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'reply',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.envelope.databaseId,
				},
			}
		},
		replyAllLink() {
			return {
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'replyAll',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.envelope.databaseId,
				},
			}
		},
		forwardLink() {
			return {
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'new',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.envelope.databaseId,
				},
			}
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
	},
	methods: {
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
	},
}
</script>
