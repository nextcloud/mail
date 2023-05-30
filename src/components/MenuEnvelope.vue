<!-- Standard Actions menu for Envelopes -->
<template>
	<Fragment>
		<template v-if="!moreActionsOpen">
			<ActionButton v-if="hasWriteAcl"
				class="action--primary"
				:close-after-click="true"
				@click.prevent="onToggleImportant">
				<template #icon>
					<ImportantIcon
						:size="20" />
				</template>
				{{
					isImportant ? t('mail', 'Unimportant') : t('mail', 'Important')
				}}
			</ActionButton>
			<ActionButton v-if="withReply"
				:close-after-click="true"
				@click="onReply">
				<template #icon>
					<ReplyAllIcon v-if="hasMultipleRecipients"
						:title="t('mail', 'Reply all')"
						:size="20" />
					<ReplyIcon v-else
						:title="t('mail', 'Reply')"
						:size="20" />
				</template>
				{{ t('mail', 'Reply') }}
			</ActionButton>
			<ActionButton v-if="hasMultipleRecipients"
				:close-after-click="true"
				@click="onReply(true)">
				<template #icon>
					<ReplyIcon
						:title="t('mail', 'Reply to sender only')"
						:size="20" />
				</template>
				{{ t('mail', 'Reply to sender only') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click="onForward">
				<template #icon>
					<ShareIcon
						:title="t('mail', 'Forward')"
						:size="20" />
				</template>
				{{ t('mail', 'Forward') }}
			</ActionButton>
			<ActionButton v-if="hasWriteAcl"
				:close-after-click="true"
				@click.prevent="onToggleJunk">
				<template #icon>
					<AlertOctagonIcon
						:title="envelope.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')"
						:size="20" />
				</template>
				{{
					envelope.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
				}}
			</ActionButton>
			<ActionButton v-if="hasWriteAcl"
				:close-after-click="true"
				@click.prevent="onOpenTagModal">
				<template #icon>
					<TagIcon
						:title="t('mail', 'Edit tags')"
						:size="20" />
				</template>
				{{ t('mail', 'Edit tags') }}
			</ActionButton>
			<ActionButton v-if="withSelect"
				:close-after-click="true"
				@click.prevent="toggleSelected">
				<template #icon>
					<CheckIcon
						:title="isSelected ? t('mail', 'Unselect') : t('mail', 'Select')"
						:size="20" />
				</template>
				{{
					isSelected ? t('mail', 'Unselect') : t('mail', 'Select')
				}}
			</ActionButton>
			<ActionButton
				v-if="hasDeleteAcl"
				:close-after-click="true"
				@click.prevent="onOpenMoveModal">
				<template #icon>
					<OpenInNewIcon
						:title="t('mail', 'Move message')"
						:size="20" />
				</template>
				{{ t('mail', 'Move message') }}
			</ActionButton>
			<ActionButton :close-after-click="false"
				@click="moreActionsOpen=true">
				<template #icon>
					<DotsHorizontalIcon
						:title="t('mail', 'More actions')"
						:size="20" />
				</template>
				{{ t('mail', 'More actions') }}
			</ActionButton>
		</template>
		<template v-else>
			<ActionButton :close-after-click="false"
				@click="moreActionsOpen=false">
				<template #icon>
					<ChevronLeft
						:title="t('mail', 'More actions')"
						:size="20" />
					{{ t('mail', 'More actions') }}
				</template>
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click.prevent="forwardSelectedAsAttachment">
				<template #icon>
					<ShareIcon
						:title="t('mail', 'Forward message as attachment')"
						:size="20" />
				</template>
				{{ t('mail', 'Forward message as attachment') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click="onOpenEditAsNew">
				<template #icon>
					<PlusIcon
						:title="t('mail', 'Edit as new message')"
						:size="20" />
				</template>
				{{ t('mail', 'Edit as new message') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click.prevent="showEventModal = true">
				<template #icon>
					<CalendarBlankIcon
						:title="t('mail', 'Create event')"
						:size="20" />
				</template>
				{{ t('mail', 'Create event') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click.prevent="showTaskModal = true">
				<template #icon>
					<TaskIcon
						:title="t('mail', 'Create task')"
						:size="20" />
				</template>
				{{ t('mail', 'Create task') }}
			</ActionButton>
			<ActionButton v-if="withShowSource"
				:close-after-click="true"
				@click.prevent="onShowSourceModal">
				<template #icon>
					<InformationIcon
						:title="t('mail', 'View source')"
						:size="20" />
				</template>
				{{ t('mail', 'View source') }}
			</ActionButton>
			<ActionLink
				:close-after-click="true"
				:href="exportMessageLink">
				<template #icon>
					<DownloadIcon :size="20" />
				</template>
				{{ t('mail', 'Download message') }}
			</ActionLink>
			<ActionLink v-if="debug"
				:download="threadingFileName"
				:href="threadingFile"
				:close-after-click="true">
				<template #icon>
					<DownloadIcon
						:title="t('mail', 'Download thread data for debugging')"
						:size="20" />
				</template>
				{{ t('mail', 'Download thread data for debugging') }}
			</ActionLink>
		</template>
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
		<TaskModal v-if="showTaskModal"
			:envelope="envelope"
			@close="showTaskModal = false" />
		<TagModal
			v-if="showTagModal"
			:account="account"
			:envelope="envelope"
			@close="onCloseTagModal" />
	</Fragment>
</template>

<script>
import axios from '@nextcloud/axios'
import { NcActionButton as ActionButton, NcActionLink as ActionLink, NcModal as Modal } from '@nextcloud/vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagon'
import { Base64 } from 'js-base64'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'
import CalendarBlankIcon from 'vue-material-design-icons/CalendarBlank'
import CheckIcon from 'vue-material-design-icons/Check'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal'
import DownloadIcon from 'vue-material-design-icons/Download'
import EventModal from './EventModal'
import TaskModal from './TaskModal'
import { mailboxHasRights } from '../util/acl'
import { generateUrl } from '@nextcloud/router'
import InformationIcon from 'vue-material-design-icons/Information'
import ImportantIcon from './icons/ImportantIcon'
import MoveModal from './MoveModal'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew'
import PlusIcon from 'vue-material-design-icons/Plus'
import ReplyIcon from 'vue-material-design-icons/Reply'
import ReplyAllIcon from 'vue-material-design-icons/ReplyAll'
import TaskIcon from 'vue-material-design-icons/CheckboxMarkedCirclePlusOutline'
import ShareIcon from 'vue-material-design-icons/Share'
import { Fragment } from 'vue-frag'

import TagIcon from 'vue-material-design-icons/Tag'
import TagModal from './TagModal'

export default {
	name: 'MenuEnvelope',
	components: {
		ActionButton,
		ActionLink,
		AlertOctagonIcon,
		CalendarBlankIcon,
		ChevronLeft,
		CheckIcon,
		DotsHorizontalIcon,
		DownloadIcon,
		EventModal,
		Fragment,
		InformationIcon,
		Modal,
		MoveModal,
		OpenInNewIcon,
		PlusIcon,
		ReplyIcon,
		ReplyAllIcon,
		ShareIcon,
		TagIcon,
		TagModal,
		ImportantIcon,
		TaskIcon,
		TaskModal,
	},
	props: {
		envelope: {
			// The envelope on which this menu will act
			type: Object,
			required: true,
		},
		mailbox: {
			// Required for checking ACLs
			type: Object,
			required: true,
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
			showSourceModal: false,
			showMoveModal: false,
			showEventModal: false,
			showTaskModal: false,
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
		showFavoriteIconVariant() {
			return this.envelope.flags.flagged
		},
		showImportantIconVariant() {
			return this.envelope.flags.seen
		},
		isImportant() {
			return this.$store.getters
				.getEnvelopeTags(this.envelope.databaseId)
				.some((tag) => tag.imapLabel === '$label1')
		},
		/**
		 * Link to download the whole message (.eml).
		 *
		 * @return {string}
		 */
		exportMessageLink() {
			return generateUrl('/apps/mail/api/messages/{id}/export', {
				id: this.envelope.databaseId,
			})
		},
		hasWriteAcl() {
			return mailboxHasRights(this.mailbox, 'w')
		},
		hasDeleteAcl() {
			return mailboxHasRights(this.mailbox, 'te')
		},
	},
	methods: {
		onForward() {
			this.$store.dispatch('startComposerSession', {
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
		async forwardSelectedAsAttachment() {
			this.forwardedMessages = [this.envelope.databaseId]
			await this.$store.dispatch('showMessageComposer', {
				forwardedMessages: this.forwardedMessages,
			})
		},
		async onShowSourceModal() {
			const resp = await axios.get(
				generateUrl('/apps/mail/api/messages/{id}/source', {
					id: this.envelope.databaseId,
				})
			)

			this.rawMessage = resp.data.source
			this.showSourceModal = true
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
		onReply(onlySender = false) {
			this.$store.dispatch('showMessageComposer', {
				reply: {
					mode: onlySender ? 'reply' : 'replyAll',
					data: this.envelope,
				},
			})
		},
		onCloseTagModal() {
			this.showTagModal = false
		},
		async onOpenEditAsNew() {
			await this.$store.dispatch('startComposerSession', {
				templateMessageId: this.envelope.databaseId,
				data: this.envelope,
			})
		},
	},
}
</script>
<style lang="scss" scoped>
	.source-modal {
		:deep(.modal-container) {
			height: 800px;
		}

		.source-modal-content {
			width: 100%;
			height: 100%;
			overflow-y: scroll !important;
		}
	}

</style>
