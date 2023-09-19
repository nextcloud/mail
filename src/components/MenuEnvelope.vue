<!-- Standard Actions menu for Envelopes -->
<template>
	<div>
		<template v-if="!localMoreActionsOpen && !snoozeActionsOpen">
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
			<ActionButton v-if="!isSnoozeDisabled"
				:close-after-click="false"
				@click="snoozeActionsOpen = true">
				<template #icon>
					<AlarmIcon :title="t('mail', 'Snooze')"
						:size="20" />
				</template>
				{{ t('mail', 'Snooze') }}
			</ActionButton>
			<ActionButton :close-after-click="false"
				@click="localMoreActionsOpen=true">
				<template #icon>
					<DotsHorizontalIcon
						:title="t('mail', 'More actions')"
						:size="20" />
				</template>
				{{ t('mail', 'More actions') }}
			</ActionButton>
		</template>
		<template v-if="localMoreActionsOpen">
			<ActionButton :close-after-click="false"
				@click="localMoreActionsOpen=false">
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
		<template v-if="snoozeActionsOpen">
			<ActionButton
				:close-after-click="false"
				@click="snoozeActionsOpen = false">
				<template #icon>
					<ChevronLeft
						:size="20" />
				</template>
				{{
					t('mail', 'Back')
				}}
			</ActionButton>

			<ActionButton v-for="option in reminderOptions"
				:key="option.key"
				:aria-label="option.ariaLabel"
				close-after-click
				@click.stop="onSnooze(option.timestamp)">
				{{ option.label }}
			</ActionButton>

			<NcActionSeparator />

			<NcActionInput type="datetime-local"
				is-native-picker
				:value="customSnoozeDateTime"
				:min="new Date()"
				@change="setCustomSnoozeDateTime">
				<template #icon>
					<CalendarClock :size="20" />
				</template>
			</NcActionInput>

			<NcActionButton :aria-label="t('spreed', 'Set custom snooze')"
				close-after-click
				@click.stop="setCustomSnooze(customSnoozeDateTime)">
				<template #icon>
					<CheckIcon :size="20" />
				</template>
				{{ t('spreed', 'Set custom snooze') }}
			</NcActionButton>
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
			:envelopes="[envelope]"
			@close="onCloseTagModal" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import {
	NcActionButton,
	NcActionButton as ActionButton,
	NcActionLink as ActionLink,
	NcModal as Modal,
} from '@nextcloud/vue'
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
import { showError, showSuccess } from '@nextcloud/dialogs'

import TagIcon from 'vue-material-design-icons/Tag'
import TagModal from './TagModal'
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput'
import AlarmIcon from 'vue-material-design-icons/Alarm'
import logger from '../logger'
import moment from '@nextcloud/moment'
import { mapGetters } from 'vuex'

export default {
	name: 'MenuEnvelope',
	components: {
		NcActionButton,
		NcActionInput,
		NcActionSeparator,
		CalendarClock,
		ActionButton,
		ActionLink,
		AlertOctagonIcon,
		CalendarBlankIcon,
		ChevronLeft,
		CheckIcon,
		DotsHorizontalIcon,
		DownloadIcon,
		EventModal,
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
		AlarmIcon,
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
		moreActionsOpen: {
			type: Boolean,
			required: false,
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
			localMoreActionsOpen: false,
			snoozeActionsOpen: false,
			forwardMessages: this.envelope.databaseId,
			customSnoozeDateTime: new Date(moment().add(2, 'hours').minute(0).second(0).valueOf()),
		}
	},
	computed: {
		...mapGetters([
			'isSnoozeDisabled',
		]),
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
		reminderOptions() {
			const currentDateTime = moment()

			// Same day 18:00 PM (or hidden)
			const laterTodayTime = (currentDateTime.hour() < 18)
				? moment().hour(18)
				: null

			// Tomorrow 08:00 AM
			const tomorrowTime = moment().add(1, 'days').hour(8)

			// Saturday 08:00 AM (or hidden)
			const thisWeekendTime = (currentDateTime.day() !== 6 && currentDateTime.day() !== 0)
				? moment().day(6).hour(8)
				: null

			// Next Monday 08:00 AM
			const nextWeekTime = moment().add(1, 'weeks').day(1).hour(8)

			return [
				{
					key: 'laterToday',
					timestamp: this.getTimestamp(laterTodayTime),
					label: t('spreed', 'Later today – {timeLocale}', { timeLocale: laterTodayTime?.format('LT') }),
					ariaLabel: t('spreed', 'Set reminder for later today'),
				},
				{
					key: 'tomorrow',
					timestamp: this.getTimestamp(tomorrowTime),
					label: t('spreed', 'Tomorrow – {timeLocale}', { timeLocale: tomorrowTime?.format('ddd LT') }),
					ariaLabel: t('spreed', 'Set reminder for tomorrow'),
				},
				{
					key: 'thisWeekend',
					timestamp: this.getTimestamp(thisWeekendTime),
					label: t('spreed', 'This weekend – {timeLocale}', { timeLocale: thisWeekendTime?.format('ddd LT') }),
					ariaLabel: t('spreed', 'Set reminder for this weekend'),
				},
				{
					key: 'nextWeek',
					timestamp: this.getTimestamp(nextWeekTime),
					label: t('spreed', 'Next week – {timeLocale}', { timeLocale: nextWeekTime?.format('ddd LT') }),
					ariaLabel: t('spreed', 'Set reminder for next week'),
				},
			].filter(option => option.timestamp !== null)
		},
	},
	watch: {
		localMoreActionsOpen(value) {
			this.$emit('update:moreActionsOpen', value)
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
		async onSnooze(timestamp) {
			// Remove from selection first
			if (this.withSelect) {
				this.$emit('unselect')
			}

			logger.info(`snoozing message ${this.envelope.databaseId}`)

			if (!this.account.snoozeMailboxId) {
				await this.$store.dispatch('createAndSetSnoozeMailbox', this.account)
			}

			try {
				await this.$store.dispatch('snoozeMessage', {
					id: this.envelope.databaseId,
					unixTimestamp: timestamp / 1000,
					destMailboxId: this.account.snoozeMailboxId,
				})
				showSuccess(t('mail', 'Message was snoozed'))
			} catch (error) {
				logger.error('Could not snooze message', error)
				showError(t('mail', 'Could not snooze message'))
			}
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
		async onToggleJunk() {
			const removeEnvelope = await this.$store.dispatch('moveEnvelopeToJunk', this.envelope)

			/**
			 * moveEnvelopeToJunk returns true if the envelope should be moved to a different mailbox.
			 *
			 * Our backend (MessageMapper.move) implemented move as copy and delete.
			 * The message is copied to another mailbox and gets a new UID; the message in the current folder is deleted.
			 *
			 * Trigger the delete event here to open the next envelope and remove the current envelope from the list.
			 * The delete event bubbles up to MailboxThread.deleteMessage and is forwarded to Mailbox.onDelete to the actual implementation.
			 *
			 * In Mailbox.onDelete, fetchNextEnvelopes requires the current envelope to find the next envelope.
			 * Therefore, it must run before removing the envelope.
			 */

			if (removeEnvelope) {
				await this.$emit('delete', this.envelope.databaseId)
			}

			await this.$store.dispatch('toggleEnvelopeJunk', {
				envelope: this.envelope,
				removeEnvelope,
			})
		},
		toggleSelected() {
			this.$emit('update:selected')
		},
		async forwardSelectedAsAttachment() {
			await this.$store.dispatch('startComposerSession', {
				forwardedMessages: [this.envelope.databaseId],
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
			this.$store.dispatch('startComposerSession', {
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
		getTimestamp(momentObject) {
			return momentObject?.minute(0).second(0).millisecond(0).valueOf() || null
		},
		setCustomSnoozeDateTime(event) {
			this.customSnoozeDateTime = new Date(event.target.value)
		},
		setCustomSnooze() {
			this.onSnooze(this.customSnoozeDateTime.valueOf())
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
