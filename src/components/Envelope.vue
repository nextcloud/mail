<template>
	<ListItem
		v-draggable-envelope="{
			accountId: data.accountId ? data.accountId : mailbox.accountId,
			mailboxId: data.mailboxId,
			databaseId: data.databaseId,
			draggableLabel,
			selectedEnvelopes,
			isDraggable,
		}"
		class="list-item-style envelope"
		:class="{seen: data.flags.seen, draft, selected: selected}"
		:to="link"
		:exact="true"
		:data-envelope-id="data.databaseId"
		:name="addresses"
		:details="formatted()"
		@click="onClick"
		@click.ctrl.prevent="toggleSelected"
		@update:menuOpen="closeMoreAndSnoozeOptions">
		<template #icon>
			<Star
				v-if="data.flags.flagged"
				fill-color="#f9cf3d"
				:size="18"
				class="app-content-list-item-star favorite-icon-style"
				:data-starred="data.flags.flagged ? 'true' : 'false'"
				@click.prevent="hasWriteAcl ? onToggleFlagged() : false" />
			<div
				v-if="isImportant"
				class="app-content-list-item-star svg icon-important"
				:data-starred="isImportant ? 'true' : 'false'"
				@click.prevent="hasWriteAcl ? onToggleImportant() : false"
				v-html="importantSvg" />
			<JunkIcon
				v-if="data.flags.$junk"
				:size="18"
				class="app-content-list-item-star junk-icon-style"
				:data-starred="data.flags.$junk ? 'true' : 'false'"
				@click.prevent="hasWriteAcl ? onToggleJunk() : false" />
			<div class="app-content-list-item-icon">
				<Avatar :display-name="addresses" :email="avatarEmail" />
				<p v-if="selectMode" class="app-content-list-item-select-checkbox">
					<input :id="`select-checkbox-${data.uid}`"
						class="checkbox"
						type="checkbox"
						:checked="selected">
					<label
						:for="`select-checkbox-${data.uid}`"
						@click.exact.prevent="toggleSelected"
						@click.shift.prevent="onSelectMultiple" />
				</p>
			</div>
		</template>
		<template #subname>
			<div class="envelope__subtitle">
				<Reply v-if="data.flags.answered"
					class="seen-icon-style"
					:size="18" />
				<IconAttachment v-if="data.flags.hasAttachments === true"
					class="attachment-icon-style"
					:size="18" />
				<span v-else-if="draft" class="draft">
					<em>{{ t('mail', 'Draft: ') }}</em>
				</span>
				<span class="envelope__subtitle__subject">
					{{ subjectForSubtitle }}
				</span>
			</div>
			<div v-if="data.encrypted || data.previewText"
				class="envelope__preview-text">
				{{ isEncrypted ? t('mail', 'Encrypted message') : data.previewText }}
			</div>
		</template>
		<template #indicator>
			<!-- Color dot -->
			<IconBullet v-if="!data.flags.seen"
				:size="16"
				:aria-hidden="false"
				:aria-label="t('mail', 'This message is unread')"
				fill-color="var(--color-primary-element)" />
		</template>
		<template #actions>
			<EnvelopePrimaryActions v-if="!moreActionsOpen && !snoozeOptions">
				<ActionButton v-if="hasWriteAcl"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleFlagged">
					<template #icon>
						<StarOutline v-if="showFavoriteIconVariant"
							:size="24" />
						<Star v-else
							:size="24" />
					</template>
					{{
						data.flags.flagged ? t('mail', 'Unfavorite') : t('mail', 'Favorite')
					}}
				</ActionButton>
				<ActionButton v-if="hasSeenAcl"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleSeen">
					<template #icon>
						<EmailUnread v-if="showImportantIconVariant"
							:size="24" />
						<EmailRead v-else
							:size="24" />
					</template>
					{{
						data.flags.seen ? t('mail', 'Unread') : t('mail', 'Read')
					}}
				</ActionButton>
				<ActionButton v-if="hasWriteAcl"
					class="action--primary"
					:close-after-click="true"
					@click.prevent="onToggleImportant">
					<template #icon>
						<ImportantIcon
							:size="24" />
					</template>
					{{
						isImportant ? t('mail', 'Unimportant') : t('mail', 'Important')
					}}
				</ActionButton>
			</EnvelopePrimaryActions>
			<template v-if="!moreActionsOpen && !snoozeOptions">
				<ActionText>
					<template #icon>
						<ClockOutlineIcon
							:size="20" />
					</template>
					{{
						messageLongDate
					}}
				</ActionText>
				<ActionSeparator />
				<ActionButton v-if="hasWriteAcl"
					:close-after-click="true"
					@click.prevent="onToggleJunk">
					<template #icon>
						<AlertOctagonIcon
							:size="20" />
					</template>
					{{
						data.flags.$junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam')
					}}
				</ActionButton>
				<ActionButton
					:close-after-click="true"
					@click.prevent="toggleSelected">
					<template #icon>
						<CheckIcon
							:size="20" />
					</template>
					{{
						selected ? t('mail', 'Unselect') : t('mail', 'Select')
					}}
				</ActionButton>
				<ActionButton v-if="hasWriteAcl"
					:close-after-click="true"
					@click.prevent="onOpenTagModal">
					<template #icon>
						<TagIcon
							:size="20" />
					</template>
					{{ t('mail', 'Edit tags') }}
				</ActionButton>
				<ActionButton v-if="!isSnoozeDisabled && !isSnoozedMailbox"
					:close-after-click="false"
					@click="showSnoozeOptions">
					<template #icon>
						<AlarmIcon
							:name="t('mail', 'Snooze')"
							:size="20" />
					</template>
					{{
						t('mail', 'Snooze')
					}}
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
				<ActionButton v-if="hasDeleteAcl"
					:close-after-click="true"
					@click.prevent="onOpenMoveModal">
					<template #icon>
						<OpenInNewIcon
							:size="20" />
					</template>
					{{ t('mail', 'Move thread') }}
				</ActionButton>
				<ActionButton v-if="showArchiveButton && hasArchiveAcl"
					:close-after-click="true"
					:disabled="disableArchiveButton"
					@click.prevent="onArchive">
					<template #icon>
						<ArchiveIcon
							:size="20" />
					</template>
					{{ t('mail', 'Archive thread') }}
				</ActionButton>
				<ActionButton v-if="hasDeleteAcl"
					:close-after-click="true"
					@click.prevent="onDelete">
					<template #icon>
						<DeleteIcon
							:size="20" />
					</template>
					{{ t('mail', 'Delete thread') }}
				</ActionButton>
				<ActionButton :close-after-click="false"
					@click="showMoreActionOptions">
					<template #icon>
						<DotsHorizontalIcon
							:size="20" />
					</template>
					{{ t('mail', 'More actions') }}
				</ActionButton>
			</template>
			<template v-if="snoozeOptions">
				<ActionButton
					:close-after-click="false"
					@click="snoozeOptions = false">
					<template #icon>
						<ChevronLeft
							:size="20" />
					</template>
					{{
						t('mail', 'Back')
					}}
				</ActionButton>

				<NcActionSeparator />

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

				<ActionButton :aria-label="t('spreed', 'Set custom snooze')"
					close-after-click
					@click.stop="setCustomSnooze(customSnoozeDateTime)">
					<template #icon>
						<CheckIcon :size="20" />
					</template>
					{{ t('spreed', 'Set custom snooze') }}
				</ActionButton>
			</template>
			<template v-if="moreActionsOpen">
				<ActionButton :close-after-click="false"
					@click="moreActionsOpen=false">
					<template #icon>
						<ChevronLeft
							:size="20" />
					</template>
					{{ t('mail', 'More actions') }}
				</ActionButton>
				<ActionButton :close-after-click="true"
					@click.prevent="onOpenEditAsNew">
					<template #icon>
						<PlusIcon
							:size="20" />
					</template>
					{{ t('mail', 'Edit as new message') }}
				</ActionButton>
				<ActionButton :close-after-click="true"
					@click.prevent="showEventModal = true">
					<template #icon>
						<IconCreateEvent
							:size="20" />
					</template>
					{{ t('mail', 'Create event') }}
				</ActionButton>
				<ActionButton :close-after-click="true"
					@click.prevent="showTaskModal = true">
					<template #icon>
						<TaskIcon
							:size="20" />
					</template>
					{{ t('mail', 'Create task') }}
				</ActionButton>
				<ActionLink
					:close-after-click="true"
					:href="exportMessageLink">
					<template #icon>
						<DownloadIcon :size="20" />
					</template>
					{{ t('mail', 'Download message') }}
				</ActionLink>
			</template>
		</template>
		<template #extra>
			<div v-for="tag in tags"
				:key="tag.id"
				class="tag-group">
				<div class="tag-group__bg"
					:style="{'background-color': tag.color}" />
				<span class="tag-group__label"
					:style="{color: tag.color}">{{ tag.displayName }} </span>
			</div>
			<MoveModal v-if="showMoveModal"
				:account="account"
				:envelopes="[data]"
				:move-thread="true"
				@move="onMove"
				@close="onCloseMoveModal" />
			<EventModal v-if="showEventModal"
				:envelope="data"
				@close="showEventModal = false" />
			<TaskModal v-if="showTaskModal"
				:envelope="data"
				@close="showTaskModal = false" />
			<TagModal
				v-if="showTagModal"
				:account="account"
				:envelopes="[data]"
				@close="onCloseTagModal" />
		</template>
	</ListItem>
</template>
<script>
import {
	NcListItem as ListItem,
	NcActionButton as ActionButton,
	NcActionLink as ActionLink,
	NcActionSeparator as ActionSeparator,
	NcActionText as ActionText,
} from '@nextcloud/vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagon.vue'
import Avatar from './Avatar.vue'
import IconCreateEvent from 'vue-material-design-icons/Calendar.vue'
import ClockOutlineIcon from 'vue-material-design-icons/ClockOutline.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ArchiveIcon from 'vue-material-design-icons/PackageDown.vue'
import TaskIcon from 'vue-material-design-icons/CheckboxMarkedCirclePlusOutline.vue'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import importantSvg from '../../img/important.svg'
import { DraggableEnvelopeDirective } from '../directives/drag-and-drop/draggable-envelope/index.js'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder.js'
import { shortRelativeDatetime, messageDateTime } from '../util/shortRelativeDatetime.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import NoTrashMailboxConfiguredError
	from '../errors/NoTrashMailboxConfiguredError.js'
import logger from '../logger.js'
import { matchError } from '../errors/match.js'
import MoveModal from './MoveModal.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import StarOutline from 'vue-material-design-icons/StarOutline.vue'
import Star from 'vue-material-design-icons/Star.vue'
import Reply from 'vue-material-design-icons/Reply.vue'
import EmailRead from 'vue-material-design-icons/EmailOpen.vue'
import EmailUnread from 'vue-material-design-icons/Email.vue'
import IconAttachment from 'vue-material-design-icons/Paperclip.vue'
import ImportantIcon from './icons/ImportantIcon.vue'
import IconBullet from 'vue-material-design-icons/CheckboxBlankCircle.vue'
import JunkIcon from './icons/JunkIcon.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import TagIcon from 'vue-material-design-icons/Tag.vue'
import TagModal from './TagModal.vue'
import EventModal from './EventModal.vue'
import TaskModal from './TaskModal.vue'
import EnvelopePrimaryActions from './EnvelopePrimaryActions.vue'
import { hiddenTags } from './tags.js'
import { generateUrl } from '@nextcloud/router'
import { isPgpText } from '../crypto/pgp.js'
import { mailboxHasRights } from '../util/acl.js'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import AlarmIcon from 'vue-material-design-icons/Alarm.vue'
import moment from '@nextcloud/moment'
import { mapGetters } from 'vuex'

export default {
	name: 'Envelope',
	components: {
		AlertOctagonIcon,
		Avatar,
		IconCreateEvent,
		CheckIcon,
		ChevronLeft,
		DeleteIcon,
		ArchiveIcon,
		TaskIcon,
		DotsHorizontalIcon,
		EnvelopePrimaryActions,
		EventModal,
		TaskModal,
		ListItem,
		ImportantIcon,
		JunkIcon,
		ActionButton,
		MoveModal,
		OpenInNewIcon,
		PlusIcon,
		TagIcon,
		TagModal,
		Star,
		StarOutline,
		EmailRead,
		EmailUnread,
		IconAttachment,
		IconBullet,
		Reply,
		ActionLink,
		ActionSeparator,
		ActionText,
		DownloadIcon,
		ClockOutlineIcon,
		NcActionSeparator,
		NcActionInput,
		CalendarClock,
		AlarmIcon,
	},
	directives: {
		draggableEnvelope: DraggableEnvelopeDirective,
	},
	props: {
		withReply: {
			// "Reply" action should only appear in envelopes from the envelope list
			// (Because in thread envelopes, this action is already set as primary button of this menu)
			type: Boolean,
			default: true,
		},
		data: {
			type: Object,
			required: true,
		},
		mailbox: {
			type: Object,
			required: true,
		},
		selectMode: {
			type: Boolean,
			default: false,
		},
		selected: {
			type: Boolean,
			default: false,
		},
		selectedEnvelopes: {
			type: Array,
			required: false,
			default: () => [],
		},
		hasMultipleAccounts: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			importantSvg,
			showMoveModal: false,
			showEventModal: false,
			showTaskModal: false,
			showTagModal: false,
			moreActionsOpen: false,
			snoozeOptions: false,
			customSnoozeDateTime: new Date(moment().add(2, 'hours').minute(0).second(0).valueOf()),
		}
	},
	computed: {
		...mapGetters([
			'isSnoozeDisabled',
		]),
		messageLongDate() {
			return messageDateTime(new Date(this.data.dateInt))
		},
		hasMultipleRecipients() {
			if (!this.account) {
				console.error('account is undefined', {
					accountId: this.data.accountId,
				})
			}
			const recipients = buildReplyRecipients(this.envelope, {
				label: this.account.name,
				email: this.account.emailAddress,
			})
			return recipients.to.concat(recipients.cc).length > 1
		},
		draft() {
			return this.data.flags.draft
		},
		account() {
			const accountId = this.data.accountId
			return this.$store.getters.getAccount(accountId)
		},
		link() {
			if (this.draft) {
				return undefined
			} else {
				return {
					name: 'message',
					params: {
						mailboxId: this.$route.params.mailboxId,
						filter: this.$route.params.filter ? this.$route.params.filter : undefined,
						threadId: this.data.databaseId,
					},
				}
			}
		},
		addresses() {
			// Show recipients' label/address in a sent mailbox
			if (this.mailbox.specialRole === 'sent' || this.account.sentMailboxId === this.mailbox.databaseId) {
				const recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.label ? recipient.label : recipient.email
				})
				return recipients.length > 0 ? recipients.join(', ') : t('mail', 'Blind copy recipients only')
			}
			// Show sender label/address in other mailbox types
			return this.data.from[0]?.label ?? this.data.from[0]?.email ?? '?'
		},
		avatarEmail() {
			// Show first recipients' avatar in a sent mailbox (or undefined when sent to Bcc only)
			if (this.mailbox.specialRole === 'sent') {
				const recipients = [this.data.to, this.data.cc].flat().map(function(recipient) {
					return recipient.email
				})
				return recipients.length > 0 ? recipients[0] : ''
			}

			// Show sender avatar in other mailbox types
			if (this.data.from.length > 0) {
				return this.data.from[0].email
			} else {
				return ''
			}
		},
		showArchiveButton() {
			return this.account.archiveMailboxId !== null
		},
		disableArchiveButton() {
			return this.account.archiveMailboxId !== null
				&& this.account.archiveMailboxId === this.mailbox.databaseId
		},
		showFavoriteIconVariant() {
			return this.data.flags.flagged
		},
		showImportantIconVariant() {
			return this.data.flags.seen
		},
		isEncrypted() {
			return this.data.encrypted // S/MIME
				|| (this.data.previewText && isPgpText(this.data.previewText)) // PGP/Mailvelope
		},
		isImportant() {
			return this.$store.getters
				.getEnvelopeTags(this.data.databaseId)
				.some((tag) => tag.imapLabel === '$label1')
		},
		tags() {
			return this.$store.getters.getEnvelopeTags(this.data.databaseId).filter(
				(tag) => tag.imapLabel && tag.imapLabel !== '$label1' && !(tag.displayName.toLowerCase() in hiddenTags)
			)
		},
		draggableLabel() {
			let label = this.data.subject
			const sender = this.data.from[0]?.label ?? this.data.from[0]?.email
			if (sender) {
				label += ` (${sender})`
			}
			return label
		},
		isDraggable() {
			return mailboxHasRights(this.mailbox, 'te')
		},
		/**
		 * Subject of envelope or "No Subject".
		 *
		 * @return {string}
		 */
		subjectForSubtitle() {
			// We have to use || here (instead of ??) because the subject might be '', null
			// or undefined.
			return this.data.subject || this.t('mail', 'No subject')
		},
		/**
		 * Link to download the whole message (.eml).
		 *
		 * @return {string}
		 */
		exportMessageLink() {
			return generateUrl('/apps/mail/api/messages/{id}/export', {
				id: this.data.databaseId,
			})
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
		archiveMailbox() {
			return this.$store.getters.getMailbox(this.account.archiveMailboxId)
		},
		isSnoozedMailbox() {
			return this.mailbox.databaseId === this.account.snoozeMailboxId
		},
		reminderOptions() {
			const currentDateTime = moment()

			// Same day 18:00 PM (hidden if after 17:00 PM now)
			const laterTodayTime = (currentDateTime.hour() < 17)
				? moment().hour(18)
				: null

			// Tomorrow 08:00 AM
			const tomorrowTime = moment().add(1, 'days').hour(8)

			// Saturday 08:00 AM (hidden if Friday, Saturday or Sunday now)
			const thisWeekendTime = (currentDateTime.day() > 0 && currentDateTime.day() < 5)
				? moment().day(6).hour(8)
				: null

			// Next Monday 08:00 AM (hidden if Sunday now)
			const nextWeekTime = (currentDateTime.day() !== 0)
				? moment().add(1, 'weeks').day(1).hour(8)
				: null

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
	methods: {
		setSelected(value) {
			if (this.selected !== value) {
				this.$emit('update:selected', value)
			}
		},
		formatted() {
			return shortRelativeDatetime(new Date(this.data.dateInt * 1000))
		},
		unselect() {
			if (this.selected) {
				this.$emit('update:selected', false)
			}
		},
		toggleSelected() {
			this.$emit('update:selected', !this.selected)
		},
		async onClick(event) {
			if (this.draft && !event.defaultPrevented) {
				await this.$store.dispatch('startComposerSession', {
					data: {
						...this.data,
						draftId: this.data.databaseId,
					},
					templateMessageId: this.data.databaseId,
				})
			}
		},
		onSelectMultiple() {
			this.$emit('select-multiple')
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.data)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.data)
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', { envelope: this.data })
		},
		async onToggleJunk() {
			const removeEnvelope = await this.$store.dispatch('moveEnvelopeToJunk', this.data)

			/**
			 * moveEnvelopeToJunk returns true if the envelope should be moved to a different mailbox.
			 *
			 * Our backend (MessageMapper.move) implemented move as copy and delete.
			 * The message is copied to another mailbox and gets a new UID; the message in the current folder is deleted.
			 *
			 * Trigger the delete event here to open the next envelope and remove the current envelope from the list.
			 * The delete event bubbles up to Mailbox.onDelete to the actual implementation.
			 *
			 * In Mailbox.onDelete, fetchNextEnvelopes requires the current envelope to find the next envelope.
			 * Therefore, it must run before removing the envelope.
			 */

			if (removeEnvelope) {
				await this.$emit('delete', this.data.databaseId)
			}

			await this.$store.dispatch('toggleEnvelopeJunk', {
				envelope: this.data,
				removeEnvelope,
			})
		},
		async onDelete() {
			// Remove from selection first
			this.setSelected(false)
			// Delete
			this.$emit('delete', this.data.databaseId)

			try {
				await this.$store.dispatch('deleteThread', {
					envelope: this.data,
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
		showMoreActionOptions() {
			this.snoozeOptions = false
			this.moreActionsOpen = true
		},
		showSnoozeOptions() {
			this.snoozeOptions = true
			this.moreActionsOpen = false
		},
		closeMoreAndSnoozeOptions() {
			this.snoozeOptions = false
			this.moreActionsOpen = false
		},
		async onArchive() {
			// Remove from selection first
			this.setSelected(false)
			// Archive
			this.$emit('archive', this.data.databaseId)

			try {
				await this.$store.dispatch('moveThread', {
					envelope: this.data,
					destMailboxId: this.account.archiveMailboxId,
				})
			} catch (error) {
				logger.error('could not archive message', error)
				showError(t('mail', 'Could not archive message'))
			}
		},
		async onSnooze(timestamp) {
			// Remove from selection first
			this.setSelected(false)

			if (!this.account.snoozeMailboxId) {
				await this.$store.dispatch('createAndSetSnoozeMailbox', this.account)
			}

			try {
				await this.$store.dispatch('snoozeThread', {
					envelope: this.data,
					unixTimestamp: timestamp / 1000,
					destMailboxId: this.account.snoozeMailboxId,
				})
				showSuccess(t('mail', 'Thread was snoozed'))
			} catch (error) {
				logger.error('could not snooze thread', error)
				showError(t('mail', 'Could not snooze thread'))
			}
		},
		async onUnSnooze() {
			// Remove from selection first
			this.setSelected(false)

			try {
				await this.$store.dispatch('unSnoozeThread', {
					envelope: this.data,
				})
				showSuccess(t('mail', 'Thread was unsnoozed'))
			} catch (error) {
				logger.error('Could not unsnooze thread', error)
				showError(t('mail', 'Could not unsnooze thread'))
			}
		},
		async onOpenEditAsNew() {
			await this.$store.dispatch('startComposerSession', {
				templateMessageId: this.data.databaseId,
				data: this.data,
			})
		},
		onOpenMoveModal() {
			this.showMoveModal = true
		},
		onOpenEventModal() {
			this.showEventModal = true
		},
		onMove() {
			this.$emit('move')
		},
		onCloseMoveModal() {
			this.showMoveModal = false
		},
		onOpenTagModal() {
			this.showTagModal = true
		},
		onCloseTagModal() {
			this.showTagModal = false
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
.mail-message-account-color {
	position: absolute;
	left: 0px;
	width: 2px;
	height: 69px;
	z-index: 1;
}

.envelope {
	.app-content-list-item-icon {
		height: 40px; // To prevent some unexpected spacing below the avatar
	}

	&__subtitle {
		display: flex;
		gap: 4px;

		&__subject {
			color: var(--color-main-text);
			line-height: 130%;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	}
	&__preview-text {
		color: var(--color-text-maxcontrast);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		font-weight: initial;
	}
}

.icon-important {
	:deep(path) {
	fill: #ffcc00;
	stroke: var(--color-main-background);
	}
	.list-item:hover &,
	.list-item:focus &,
	.list-item.active & {
	:deep(path) {
	stroke: var(--color-background-dark);
	}
	}

	// In message list, but not the one in the action menu
	&.app-content-list-item-star {
	background-image: none;
	left: 7px;
	top: 13px;
	opacity: 1;

	&:hover,
	&:focus {
	opacity: 0.5;
	}
	}
}

.app-content-list-item-select-checkbox {
	display: inline-block;
	vertical-align: middle;
	position: absolute;
	left: 33px;
	top: 35px;
	z-index: 50; // same as icon-starred
}

.list-item-style:not(.seen) {
	font-weight: bold;
}
.list-item-style.selected {
	background-color: var(--color-background-dark);
}
.list-item-style {
	.draft {
		line-height: 130%;

		em {
			font-style: italic;
		}
	}
}
.junk-icon-style {
	opacity: .2;
	display: flex;
	top: 42px;
	left: 32px;
	background-size: 16px;
	height: 20px;
	width: 20px;
	margin: 0;
	padding: 0;
	position: absolute;
	z-index: 2;
	&:hover {
		opacity: .1;
	}
}

.icon-attachment {
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=25)';
	opacity: 0.25;
}

:deep(.action--primary) {
	.material-design-icon {
		margin-bottom: -14px;
	}
}
:deep(.list-item__extra) {
	margin-left: 41px !important;
}
.tag-group__label {
	margin: 0 7px;
	z-index: 2;
	font-size: calc(var(--default-font-size) * 0.8);
	font-weight: bold;
	padding-left: 2px;
	padding-right: 2px;
}
.tag-group__bg {
	position: absolute;
	width: 100%;
	height: 100%;
	top: 0;
	left: 0;
	opacity: 15%;
}
.tag-group {
	display: inline-block;
	border: 1px solid transparent;
	border-radius: var(--border-radius-pill);
	position: relative;
	margin: 0 1px;
	overflow: hidden;
	left: 4px;
}
.list-item__wrapper:deep() {
	list-style: none;
}
.app-content-list-item-star.favorite-icon-style {
	display: block;
}
.icon-important.app-content-list-item-star:deep() {
	position: absolute;
	top: 14px;
	z-index: 1;
}
.app-content-list-item-star.favorite-icon-style {
	display: inline-block;
	position: absolute;
	margin-bottom: 21px;
	margin-left: 28px;
	cursor: pointer;
	stroke: var(--color-main-background);
	stroke-width: 2;
	z-index: 1;
	&:hover {
		opacity: .4;
	}
}
:deep(.svg svg) {
	height: 16px;
	width: 16px;
}
.seen-icon-style {
	opacity: .6;
}
.attachment-icon-style {
	opacity: .6;
}
:deep(.list-item-content__wrapper) {
	margin-top: 6px;
}
:deep(.list-item__extra) {
	margin-top: 9px;
}
</style>
