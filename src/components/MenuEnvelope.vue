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
						:name="t('mail', 'Reply all')"
						:size="20" />
					<ReplyIcon v-else
						:name="t('mail', 'Reply')"
						:size="20" />
				</template>
				{{ t('mail', 'Reply') }}
			</ActionButton>
			<ActionButton v-if="hasMultipleRecipients"
				:close-after-click="true"
				@click="onReply(true)">
				<template #icon>
					<ReplyIcon
						:name="t('mail', 'Reply to sender only')"
						:size="20" />
				</template>
				{{ t('mail', 'Reply to sender only') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click="onForward">
				<template #icon>
					<ShareIcon
						:name="t('mail', 'Forward')"
						:size="20" />
				</template>
				{{ t('mail', 'Forward') }}
			</ActionButton>
			<ActionButton v-if="hasWriteAcl"
				:close-after-click="true"
				@click.prevent="onToggleJunk">
				<template #icon>
					<AlertOctagonIcon
						:name="envelope.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')"
						:size="20" />
				</template>
				{{
					envelope.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
				}}
			</ActionButton>
			<ActionButton v-if="hasWriteAcl"
				:close-after-click="true"
				@click.prevent="$emit('open-tag-modal')">
				<template #icon>
					<TagIcon
						:name="t('mail', 'Edit tags')"
						:size="20" />
				</template>
				{{ t('mail', 'Edit tags') }}
			</ActionButton>
			<ActionButton v-if="withSelect"
				:close-after-click="true"
				@click.prevent="toggleSelected">
				<template #icon>
					<CheckIcon
						:name="isSelected ? t('mail', 'Unselect') : t('mail', 'Select')"
						:size="20" />
				</template>
				{{
					isSelected ? t('mail', 'Unselect') : t('mail', 'Select')
				}}
			</ActionButton>
			<ActionButton
				v-if="hasDeleteAcl"
				:close-after-click="true"
				@click.prevent="$emit('open-move-modal')">
				<template #icon>
					<OpenInNewIcon
						:name="t('mail', 'Move message')"
						:size="20" />
				</template>
				{{ t('mail', 'Move message') }}
			</ActionButton>
			<ActionButton v-if="!isSnoozeDisabled && !isSnoozedMailbox"
				:close-after-click="false"
				@click="snoozeActionsOpen = true">
				<template #icon>
					<AlarmIcon :name="t('mail', 'Snooze')"
						:size="20" />
				</template>
				{{ t('mail', 'Snooze') }}
			</ActionButton>
			<ActionButton v-if="!isSnoozeDisabled && isSnoozedMailbox"
				:close-after-click="true"
				@click="onUnSnooze">
				<template #icon>
					<AlarmIcon :title="t('mail', 'Unsnooze')"
						:size="20" />
				</template>
				{{ t('mail', 'Unsnooze') }}
			</ActionButton>
			<ActionButton :close-after-click="false"
				@click="localMoreActionsOpen=true">
				<template #icon>
					<DotsHorizontalIcon
						:name="t('mail', 'More actions')"
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
						:name="t('mail', 'More actions')"
						:size="20" />
					{{ t('mail', 'More actions') }}
				</template>
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click.prevent="forwardSelectedAsAttachment">
				<template #icon>
					<ShareIcon
						:name="t('mail', 'Forward message as attachment')"
						:size="20" />
				</template>
				{{ t('mail', 'Forward message as attachment') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click="onOpenEditAsNew">
				<template #icon>
					<PlusIcon
						:name="t('mail', 'Edit as new message')"
						:size="20" />
				</template>
				{{ t('mail', 'Edit as new message') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click.prevent="$emit('open-event-modal')">
				<template #icon>
					<CalendarBlankIcon
						:name="t('mail', 'Create event')"
						:size="20" />
				</template>
				{{ t('mail', 'Create event') }}
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click.prevent="$emit('open-task-modal')">
				<template #icon>
					<TaskIcon
						:name="t('mail', 'Create task')"
						:size="20" />
				</template>
				{{ t('mail', 'Create task') }}
			</ActionButton>
			<ActionButton v-if="withShowSource"
				:close-after-click="true"
				@click.prevent="$emit('show-source-modal')">
				<template #icon>
					<InformationIcon
						:name="t('mail', 'View source')"
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
						:name="t('mail', 'Download thread data for debugging')"
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
	</div>
</template>

<script>
import {
	NcActionButton,
	NcActionButton as ActionButton,
	NcActionLink as ActionLink,
} from '@nextcloud/vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagon.vue'
import { Base64 } from 'js-base64'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder.js'
import CalendarBlankIcon from 'vue-material-design-icons/CalendarBlank.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import { mailboxHasRights } from '../util/acl.js'
import { generateUrl } from '@nextcloud/router'
import InformationIcon from 'vue-material-design-icons/Information.vue'
import ImportantIcon from './icons/ImportantIcon.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import ReplyIcon from 'vue-material-design-icons/Reply.vue'
import ReplyAllIcon from 'vue-material-design-icons/ReplyAll.vue'
import TaskIcon from 'vue-material-design-icons/CheckboxMarkedCirclePlusOutline.vue'
import ShareIcon from 'vue-material-design-icons/Share.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'

import TagIcon from 'vue-material-design-icons/Tag.vue'
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import AlarmIcon from 'vue-material-design-icons/Alarm.vue'
import logger from '../logger.js'
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
		InformationIcon,
		OpenInNewIcon,
		PlusIcon,
		ReplyIcon,
		ReplyAllIcon,
		ShareIcon,
		TagIcon,
		ImportantIcon,
		TaskIcon,
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
		isSnoozedMailbox() {
			return this.mailbox.databaseId === this.account.snoozeMailboxId
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
		async onUnSnooze() {
			// Remove from selection first
			if (this.withSelect) {
				this.$emit('unselect')
			}

			logger.info(`unSnoozing message ${this.envelope.databaseId}`)

			try {
				await this.$store.dispatch('unSnoozeMessage', {
					id: this.envelope.databaseId,
				})
				showSuccess(t('mail', 'Message was unsnoozed'))
			} catch (error) {
				logger.error('Could not unsnooze message', error)
				showError(t('mail', 'Could not unsnooze message'))
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
		onReply(onlySender = false) {
			this.$store.dispatch('startComposerSession', {
				reply: {
					mode: onlySender ? 'reply' : 'replyAll',
					data: this.envelope,
				},
			})
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
